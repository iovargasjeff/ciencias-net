import { z } from 'zod'

export const generateObligationSchema = z.object({
  concept_id: z.string().min(1, 'El concepto es obligatorio'),
  student_ids: z.array(z.string()).min(1, 'Debe seleccionar al menos un estudiante'),
  apply_benefits: z.boolean(),
})

export type GenerateObligationFormValues = z.infer<typeof generateObligationSchema>

export const adjustObligationSchema = z.object({
  reason: z.string().min(5, 'El motivo debe tener al menos 5 caracteres'),
  due_date: z.string().optional(),
  early_payment_deadline: z.string().optional(),
  base_amount: z.string().regex(/^\d+(\.\d{1,2})?$/, 'Formato de monto inválido').optional(),
})

export type AdjustObligationFormValues = z.infer<typeof adjustObligationSchema>

export const registerPaymentSchema = z.object({
  obligation_id: z.string().min(1, 'Obligación requerida'),
  payment_method: z.enum(['cash', 'transfer', 'yape', 'plin', 'other']),
  reference: z.string().optional(),
  voucher: z.any().optional(), // File mock
}).refine(data => {
  if (data.payment_method !== 'cash' && !data.reference) {
    return false
  }
  return true
}, {
  message: 'La referencia es obligatoria para medios digitales',
  path: ['reference']
})

export type RegisterPaymentFormValues = z.infer<typeof registerPaymentSchema>
