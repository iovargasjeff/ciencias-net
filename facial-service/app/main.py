import base64
import hashlib
import json
import os
from dataclasses import dataclass
from typing import Annotated, Any

from fastapi import Depends, FastAPI, File, Form, Header, HTTPException, UploadFile
from fastapi.responses import JSONResponse

app = FastAPI(title="CienciasNET Facial Service", docs_url=None, redoc_url=None)
MODEL_VERSION = "cienciasnet-digest-face-v1"
MAX_IMAGE_BYTES = 5 * 1024 * 1024


@dataclass(frozen=True)
class ImageMetrics:
    digest: bytes
    quality: float
    liveness: float


def require_service_token(x_facial_service_token: Annotated[str | None, Header()] = None) -> None:
    expected = os.getenv("FACIAL_SERVICE_TOKEN", "")
    if not expected or x_facial_service_token != expected:
        raise HTTPException(status_code=403, detail={"code": "forbidden", "message": "Invalid service token."})


@app.exception_handler(HTTPException)
async def http_exception_handler(_request, exc: HTTPException) -> JSONResponse:
    detail = exc.detail if isinstance(exc.detail, dict) else {"code": "http_error", "message": str(exc.detail)}
    return JSONResponse(status_code=exc.status_code, content=detail)


@app.get("/health")
def health() -> dict[str, str]:
    return {"status": "ok"}


@app.post("/v1/enrollments", dependencies=[Depends(require_service_token)])
async def create_facial_embedding(images: Annotated[list[UploadFile], File()]) -> dict[str, Any]:
    if len(images) < 3 or len(images) > 5:
        raise processing_error("Enrollment requires 3 to 5 images.")

    metrics = [await inspect_image(image) for image in images]
    embedding = build_embedding(metrics)

    return {
        "embedding": embedding,
        "quality": round(sum(item.quality for item in metrics) / len(metrics), 4),
        "liveness": round(min(item.liveness for item in metrics), 4),
        "model_version": MODEL_VERSION,
    }


@app.post("/v1/identifications", dependencies=[Depends(require_service_token)])
async def identify_face(
    idempotency_key: Annotated[str, Header(min_length=8, max_length=191)],
    image: Annotated[UploadFile, File()],
    candidates: Annotated[str, Form()],
) -> dict[str, Any]:
    del idempotency_key
    metric = await inspect_image(image)
    parsed_candidates = parse_candidates(candidates)

    best_candidate_id: str | None = None
    best_confidence = 0.0

    for candidate in parsed_candidates:
        candidate_id = candidate.get("id")
        candidate_embedding = candidate.get("embedding")
        if not isinstance(candidate_id, str) or not isinstance(candidate_embedding, str):
            continue
        confidence = compare_embeddings(metric.digest, candidate_embedding)
        if confidence > best_confidence:
            best_confidence = confidence
            best_candidate_id = candidate_id

    matched = best_candidate_id is not None and best_confidence >= 0.85 and metric.liveness >= 0.65

    return {
        "matched": matched,
        "candidate_id": best_candidate_id if matched else None,
        "confidence": round(best_confidence, 4),
        "quality": metric.quality,
        "liveness": metric.liveness,
        "model_version": MODEL_VERSION,
    }


async def inspect_image(image: UploadFile) -> ImageMetrics:
    content = await image.read()
    await image.close()

    if not content:
        raise processing_error("Image is empty.")
    if len(content) > MAX_IMAGE_BYTES:
        raise processing_error("Image exceeds max size.")
    if not looks_like_image(content):
        raise processing_error("Image format is not supported.")

    digest = hashlib.sha256(content).digest()
    quality = min(1.0, max(0.1, len(set(content[:4096])) / 128))
    liveness = min(1.0, max(0.1, len(content) / 2048))

    return ImageMetrics(digest=digest, quality=round(quality, 4), liveness=round(liveness, 4))


def looks_like_image(content: bytes) -> bool:
    return (
        content.startswith(b"\xff\xd8\xff")
        or content.startswith(b"\x89PNG\r\n\x1a\n")
        or content.startswith(b"RIFF") and content[8:12] == b"WEBP"
    )


def build_embedding(metrics: list[ImageMetrics]) -> str:
    digest = hashlib.sha256(b"".join(item.digest for item in metrics)).digest()
    return base64.b64encode(digest).decode("ascii")


def parse_candidates(candidates: str) -> list[dict[str, Any]]:
    try:
        parsed = json.loads(candidates)
    except json.JSONDecodeError as exc:
        raise processing_error("Candidates must be valid JSON.") from exc
    if not isinstance(parsed, list):
        raise processing_error("Candidates must be an array.")
    return parsed


def compare_embeddings(image_digest: bytes, candidate_embedding: str) -> float:
    try:
        candidate_digest = base64.b64decode(candidate_embedding, validate=True)
    except ValueError:
        return 0.0

    expected = hashlib.sha256(image_digest).digest()
    size = min(len(expected), len(candidate_digest))
    if size == 0:
        return 0.0

    matching = sum(1 for index in range(size) if expected[index] == candidate_digest[index])
    return matching / size


def processing_error(message: str) -> HTTPException:
    return HTTPException(status_code=422, detail={"code": "processing_error", "message": message})
