import axios, { AxiosError } from 'axios'

export interface ApiErrorBody {
  error: {
    code: string
    message: string
    fields: Record<string, string[]>
  }
}

export const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? 'http://localhost:8000',
  headers: { Accept: 'application/json' },
  withCredentials: true,
  withXSRFToken: true,
})

export async function initializeCsrf(): Promise<void> {
  await apiClient.get('/sanctum/csrf-cookie')
}

export function getApiError(error: unknown): ApiErrorBody['error'] {
  if (error instanceof AxiosError && error.response?.data?.error) {
    return error.response.data.error as ApiErrorBody['error']
  }

  return {
    code: 'network_error',
    message: 'No fue posible conectar con CienciasNET.',
    fields: {},
  }
}

apiClient.interceptors.response.use(
  (response) => response,
  (error: AxiosError<ApiErrorBody>) => {
    if (error.response?.status === 401) {
      window.dispatchEvent(new CustomEvent('cienciasnet:session-expired'))
    }
    return Promise.reject(error)
  },
)
