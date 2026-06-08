import base64
import json
import os
from dataclasses import dataclass
from io import BytesIO
from typing import Annotated, Any

import face_recognition
import numpy as np
from fastapi import Depends, FastAPI, File, Form, Header, HTTPException, UploadFile
from fastapi.responses import JSONResponse

app = FastAPI(title="CienciasNET Facial Service", docs_url=None, redoc_url=None)
MODEL_VERSION = "cienciasnet-face-recognition-v1"
MAX_IMAGE_BYTES = 5 * 1024 * 1024
FACE_ENCODING_SIZE = 128
FACE_DISTANCE_TOLERANCE = float(os.getenv("FACIAL_MATCH_DISTANCE_TOLERANCE", "0.6"))
FACE_DETECTION_MODEL = os.getenv("FACIAL_DETECTION_MODEL", "hog")


@dataclass(frozen=True)
class FaceMetrics:
    encoding: np.ndarray
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

    metrics = [await inspect_face(image) for image in images]
    embedding = encode_embedding(np.mean([item.encoding for item in metrics], axis=0))

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
    metric = await inspect_face(image)
    parsed_candidates = parse_candidates(candidates)

    best_candidate_id: str | None = None
    best_distance: float | None = None

    for candidate in parsed_candidates:
        candidate_id = candidate.get("id")
        candidate_embedding = candidate.get("embedding")
        if not isinstance(candidate_id, str) or not isinstance(candidate_embedding, str):
            continue

        candidate_encoding = decode_embedding(candidate_embedding)
        if candidate_encoding is None:
            continue

        distance = float(face_recognition.face_distance([candidate_encoding], metric.encoding)[0])
        if best_distance is None or distance < best_distance:
            best_distance = distance
            best_candidate_id = candidate_id

    confidence = confidence_from_distance(best_distance)
    matched = best_candidate_id is not None and confidence >= 0.85 and metric.liveness >= 0.65

    return {
        "matched": matched,
        "candidate_id": best_candidate_id if matched else None,
        "confidence": round(confidence, 4),
        "quality": metric.quality,
        "liveness": metric.liveness,
        "model_version": MODEL_VERSION,
    }


async def inspect_face(image: UploadFile) -> FaceMetrics:
    content = await image.read()
    await image.close()

    if not content:
        raise processing_error("Image is empty.")
    if len(content) > MAX_IMAGE_BYTES:
        raise processing_error("Image exceeds max size.")

    try:
        loaded_image = face_recognition.load_image_file(BytesIO(content))
    except Exception as exc:
        raise processing_error("Image format is not supported.") from exc

    face_locations = face_recognition.face_locations(loaded_image, model=FACE_DETECTION_MODEL)
    if len(face_locations) == 0:
        raise processing_error("No face was detected in the image.")
    if len(face_locations) > 1:
        raise processing_error("More than one face was detected in the image.")

    face_encodings = face_recognition.face_encodings(loaded_image, face_locations)
    if len(face_encodings) != 1:
        raise processing_error("Face encoding could not be generated.")

    return FaceMetrics(
        encoding=np.asarray(face_encodings[0], dtype=np.float64),
        quality=quality_score(loaded_image, face_locations[0]),
        liveness=liveness_score(loaded_image, face_locations[0]),
    )


def quality_score(image: np.ndarray, face_location: tuple[int, int, int, int]) -> float:
    top, right, bottom, left = face_location
    image_height, image_width = image.shape[:2]
    face_area = max(0, right - left) * max(0, bottom - top)
    image_area = max(1, image_height * image_width)
    face_ratio = face_area / image_area

    if face_ratio < 0.03:
        return 0.35
    if face_ratio < 0.08:
        return 0.65
    if face_ratio > 0.85:
        return 0.7

    return 0.95


def liveness_score(image: np.ndarray, face_location: tuple[int, int, int, int]) -> float:
    # Heuristica inicial: el servicio exige un unico rostro detectable y un recorte facial con variacion visual.
    # Anti-spoofing real queda fuera de esta iteracion y debe integrarse como modelo separado.
    top, right, bottom, left = face_location
    face_crop = image[top:bottom, left:right]
    if face_crop.size == 0:
        return 0.1

    variation = float(np.std(face_crop))
    if variation < 12:
        return 0.45
    if variation < 24:
        return 0.7

    return 0.9


def encode_embedding(encoding: np.ndarray) -> str:
    normalized = np.asarray(encoding, dtype=np.float64)
    return base64.b64encode(normalized.tobytes()).decode("ascii")


def decode_embedding(candidate_embedding: str) -> np.ndarray | None:
    try:
        raw = base64.b64decode(candidate_embedding, validate=True)
    except ValueError:
        return None

    encoding = np.frombuffer(raw, dtype=np.float64)
    if encoding.size != FACE_ENCODING_SIZE:
        return None

    return encoding


def parse_candidates(candidates: str) -> list[dict[str, Any]]:
    try:
        parsed = json.loads(candidates)
    except json.JSONDecodeError as exc:
        raise processing_error("Candidates must be valid JSON.") from exc
    if not isinstance(parsed, list):
        raise processing_error("Candidates must be an array.")
    return parsed


def confidence_from_distance(distance: float | None) -> float:
    if distance is None:
        return 0.0

    if distance <= FACE_DISTANCE_TOLERANCE:
        margin = max(0.0, FACE_DISTANCE_TOLERANCE - distance) / FACE_DISTANCE_TOLERANCE
        return min(1.0, 0.85 + (margin * 0.15))

    overflow = min(distance - FACE_DISTANCE_TOLERANCE, FACE_DISTANCE_TOLERANCE) / FACE_DISTANCE_TOLERANCE
    return max(0.0, 0.8499 * (1.0 - overflow))


def processing_error(message: str) -> HTTPException:
    return HTTPException(status_code=422, detail={"code": "processing_error", "message": message})
