import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { X } from '@phosphor-icons/react'
import { generateObligationSchema, type GenerateObligationFormValues, adjustObligationSchema, type AdjustObligationFormValues } from './schemas'
import { generatePaymentObligations, adjustPaymentObligation } from './api'
import type { PaymentObligation } from './types'

export function GenerateObligationForm({ onSuccess, onCancel }: { onSuccess: () => void, onCancel: () => void }) {
  const client = useQueryClient()
  const [errorMsg, setErrorMsg] = useState('')
  
  const form = useForm<GenerateObligationFormValues>({
    resolver: zodResolver(generateObligationSchema),
    defaultValues: { concept_id: '', student_ids: ['a1', 'a2'], apply_benefits: true }
  })

  const mutation = useMutation({
    mutationFn: (values: GenerateObligationFormValues) => generatePaymentObligations(values),
    onSuccess: async () => {
      await client.invalidateQueries({ queryKey: ['payment-obligations'] })
      onSuccess()
    },
    onError: (err) => setErrorMsg(err instanceof Error ? err.message : 'Error desconocido')
  })

  return (
    <div className="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
      <div className="bg-white rounded-lg shadow-xl w-full max-w-md overflow-hidden flex flex-col max-h-[90vh]">
        <div className="p-4 border-b flex justify-between items-center bg-gray-50">
          <h2 className="text-lg font-semibold">Generar Deudas</h2>
          <button onClick={onCancel} className="text-gray-400 hover:text-gray-600"><X size={20} /></button>
        </div>
        <form onSubmit={form.handleSubmit((v) => mutation.mutate(v))} className="p-6 overflow-y-auto space-y-4">
          {errorMsg && <div className="bg-red-50 text-red-600 p-3 rounded text-sm">{errorMsg}</div>}
          
          <div>
            <label className="block text-sm font-medium mb-1">Concepto ID (Simulado)</label>
            <input {...form.register('concept_id')} className="w-full border rounded p-2" placeholder="Ej. c1" />
            {form.formState.errors.concept_id && <p className="text-red-500 text-xs mt-1">{form.formState.errors.concept_id.message}</p>}
          </div>

          <div>
            <label className="block text-sm font-medium mb-1">Aplicar Beneficios Automáticos</label>
            <input type="checkbox" {...form.register('apply_benefits')} className="w-4 h-4" />
          </div>

          <div className="pt-4 flex justify-end gap-3 border-t">
            <button type="button" onClick={onCancel} className="px-4 py-2 border rounded hover:bg-gray-50">Cancelar</button>
            <button type="submit" disabled={mutation.isPending} className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50">
              {mutation.isPending ? 'Generando...' : 'Generar'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}

export function AdjustObligationForm({ obligation, onSuccess, onCancel }: { obligation: PaymentObligation, onSuccess: () => void, onCancel: () => void }) {
  const client = useQueryClient()
  const [errorMsg, setErrorMsg] = useState('')
  
  const form = useForm<AdjustObligationFormValues>({
    resolver: zodResolver(adjustObligationSchema),
    defaultValues: { reason: '', due_date: obligation.due_date, base_amount: obligation.base_amount }
  })

  const mutation = useMutation({
    mutationFn: (values: AdjustObligationFormValues) => adjustPaymentObligation(obligation.id, values),
    onSuccess: async () => {
      await client.invalidateQueries({ queryKey: ['payment-obligations'] })
      onSuccess()
    },
    onError: (err) => setErrorMsg(err instanceof Error ? err.message : 'Error desconocido')
  })

  return (
    <div className="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
      <div className="bg-white rounded-lg shadow-xl w-full max-w-md overflow-hidden flex flex-col max-h-[90vh]">
        <div className="p-4 border-b flex justify-between items-center bg-gray-50">
          <h2 className="text-lg font-semibold">Ajustar Deuda: {obligation.concept_name}</h2>
          <button onClick={onCancel} className="text-gray-400 hover:text-gray-600"><X size={20} /></button>
        </div>
        <form onSubmit={form.handleSubmit((v) => mutation.mutate(v))} className="p-6 overflow-y-auto space-y-4">
          {errorMsg && <div className="bg-red-50 text-red-600 p-3 rounded text-sm">{errorMsg}</div>}
          
          <div>
            <label className="block text-sm font-medium mb-1">Monto Base</label>
            <input {...form.register('base_amount')} className="w-full border rounded p-2" />
            {form.formState.errors.base_amount && <p className="text-red-500 text-xs mt-1">{form.formState.errors.base_amount.message}</p>}
          </div>

          <div>
            <label className="block text-sm font-medium mb-1">Fecha de Vencimiento</label>
            <input type="date" {...form.register('due_date')} className="w-full border rounded p-2" />
            {form.formState.errors.due_date && <p className="text-red-500 text-xs mt-1">{form.formState.errors.due_date.message}</p>}
          </div>

          <div>
            <label className="block text-sm font-medium mb-1">Motivo del Ajuste</label>
            <textarea {...form.register('reason')} className="w-full border rounded p-2" rows={3}></textarea>
            {form.formState.errors.reason && <p className="text-red-500 text-xs mt-1">{form.formState.errors.reason.message}</p>}
          </div>

          <div className="pt-4 flex justify-end gap-3 border-t">
            <button type="button" onClick={onCancel} className="px-4 py-2 border rounded hover:bg-gray-50">Cancelar</button>
            <button type="submit" disabled={mutation.isPending} className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50">
              {mutation.isPending ? 'Guardando...' : 'Guardar Ajuste'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}
