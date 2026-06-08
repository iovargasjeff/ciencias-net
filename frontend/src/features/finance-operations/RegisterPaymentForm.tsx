import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { X } from '@phosphor-icons/react'
import { registerPaymentSchema, type RegisterPaymentFormValues } from './schemas'
import { createPaymentMovement } from './api'
import type { PaymentObligation } from './types'

export function RegisterPaymentForm({ obligation, onSuccess, onCancel }: { obligation: PaymentObligation, onSuccess: () => void, onCancel: () => void }) {
  const client = useQueryClient()
  const [errorMsg, setErrorMsg] = useState('')
  
  const form = useForm<RegisterPaymentFormValues>({
    resolver: zodResolver(registerPaymentSchema),
    defaultValues: { obligation_id: obligation.id, payment_method: 'cash', reference: '' }
  })

  const method = form.watch('payment_method')

  const mutation = useMutation({
    mutationFn: (values: RegisterPaymentFormValues) => createPaymentMovement(values),
    onSuccess: async () => {
      await client.invalidateQueries({ queryKey: ['payment-obligations'] })
      await client.invalidateQueries({ queryKey: ['payment-movements'] })
      onSuccess()
    },
    onError: (err) => setErrorMsg(err instanceof Error ? err.message : 'Error desconocido')
  })

  return (
    <div className="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
      <div className="bg-white rounded-lg shadow-xl w-full max-w-md overflow-hidden flex flex-col max-h-[90vh]">
        <div className="p-4 border-b flex justify-between items-center bg-gray-50">
          <h2 className="text-lg font-semibold">Registrar Pago</h2>
          <button onClick={onCancel} className="text-gray-400 hover:text-gray-600"><X size={20} /></button>
        </div>
        <form onSubmit={form.handleSubmit((v) => mutation.mutate(v))} className="p-6 overflow-y-auto space-y-4">
          {errorMsg && <div className="bg-red-50 text-red-600 p-3 rounded text-sm">{errorMsg}</div>}
          
          <div className="bg-blue-50 p-3 rounded text-sm text-blue-800">
            <strong>Alumno:</strong> {obligation.student_name} <br/>
            <strong>Deuda:</strong> {obligation.concept_name} <br/>
            <strong>Monto a pagar:</strong> S/ {obligation.early_payment_amount || obligation.base_amount} 
            {obligation.early_payment_amount ? ' (Pronto Pago aplicado)' : ''}
          </div>

          <div>
            <label className="block text-sm font-medium mb-1">Medio de Pago</label>
            <select {...form.register('payment_method')} className="w-full border rounded p-2 bg-white">
              <option value="cash">Efectivo</option>
              <option value="transfer">Transferencia Bancaria</option>
              <option value="yape">Yape</option>
              <option value="plin">Plin</option>
              <option value="other">Otro</option>
            </select>
          </div>

          {method !== 'cash' && (
            <div>
              <label className="block text-sm font-medium mb-1">Número de Referencia</label>
              <input {...form.register('reference')} className="w-full border rounded p-2" placeholder="Ej. OP-12345" />
              {form.formState.errors.reference && <p className="text-red-500 text-xs mt-1">{form.formState.errors.reference.message}</p>}
            </div>
          )}

          <div>
            <label className="block text-sm font-medium mb-1">Subir Comprobante (Simulado)</label>
            <input type="file" {...form.register('voucher')} className="w-full text-sm" />
          </div>

          <div className="pt-4 flex justify-end gap-3 border-t">
            <button type="button" onClick={onCancel} className="px-4 py-2 border rounded hover:bg-gray-50">Cancelar</button>
            <button type="submit" disabled={mutation.isPending} className="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50">
              {mutation.isPending ? 'Registrando...' : 'Confirmar Pago'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}
