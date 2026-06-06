export interface AuthUser {
  id: string
  name: string
  email: string
  roles: string[]
}

export interface LoginInput {
  email: string
  password: string
  remember: boolean
}
