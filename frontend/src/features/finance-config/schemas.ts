import { z } from 'zod'

export const createPaymentConceptSchema = z.object({
  code: z.string().min(1, 'El código es requerido').max(30),
  name: z.string().min(1, 'El nombre es requerido').max(150),
  amount: z.string().regex(/^\d{1,10}(\.\d{1,2})?$/, 'Monto inválido (ej. 150.00)'),
  due_day: z.number().min(1).max(31).optional(),
  recurrence: z.enum(['single', 'monthly', 'annual']).optional(),
  early_payment_discount: z.string().regex(/^\d{1,10}(\.\d{1,2})?$/, 'Monto inválido').optional(),
  early_payment_deadline: z.string().optional(), // Should be date format YYYY-MM-DD
})

export type CreatePaymentConceptInput = z.infer<typeof createPaymentConceptSchema>

export const updatePaymentConceptSchema = createPaymentConceptSchema.partial()

export type UpdatePaymentConceptInput = z.infer<typeof updatePaymentConceptSchema>

export const createStudentBenefitSchema = z.object({
  student_id: z.string().uuid('ID de estudiante inválido'),
  benefit_type: z.enum(['percentage', 'fixed', 'waiver']),
  value: z.string().regex(/^\d{1,10}(\.\d{1,2})?$/, 'Valor inválido').nullable().optional(),
  concept_ids: z.array(z.string().uuid()).optional(),
  stackable_with_early_payment: z.boolean(),
  starts_on: z.string().min(1, 'La fecha de inicio es requerida'), // Should be YYYY-MM-DD
  ends_on: z.string().optional(),
}).refine(data => {
  if (data.benefit_type !== 'waiver' && !data.value) {
    return false
  }
  return true
}, {
  message: 'El valor es requerido para becas y descuentos',
  path: ['value'],
})

export type CreateStudentBenefitInput = z.infer<typeof createStudentBenefitSchema>

export const deactivateBenefitSchema = z.object({
  reason: z.string().min(5, 'Debe proveer un motivo de al menos 5 caracteres'),
})

export type DeactivateBenefitInput = z.infer<typeof deactivateBenefitSchema>
