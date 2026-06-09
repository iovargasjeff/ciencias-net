import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { createPaymentConceptSchema, type CreatePaymentConceptInput } from './schemas'
import { useCreatePaymentConcept } from './hooks'

export function PaymentConceptForm({ onSuccess, onCancel }: { onSuccess: () => void; onCancel: () => void }) {
  const { register, handleSubmit, formState: { errors } } = useForm<CreatePaymentConceptInput>({
    resolver: zodResolver(createPaymentConceptSchema),
    defaultValues: {
      recurrence: 'monthly'
    }
  })
  
  const { mutate, isPending, isError, error } = useCreatePaymentConcept()

  const onSubmit = (data: CreatePaymentConceptInput) => {
    mutate(data, {
      onSuccess: () => {
        onSuccess()
      }
    })
  }

  return (
    <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-lg shadow-xl w-full max-w-md overflow-hidden">
        <div className="px-6 py-4 border-b">
          <h2 className="text-xl font-bold">Nuevo Concepto de Pago</h2>
        </div>
        
        <form onSubmit={handleSubmit(onSubmit)} className="p-6 space-y-4">
          {isError && (
            <div className="p-3 bg-red-50 text-red-700 rounded text-sm">
              {error instanceof Error ? error.message : 'Ocurrió un error al guardar.'}
            </div>
          )}

          <div>
            <label className="block text-sm font-medium mb-1">Código</label>
            <input 
              {...register('code')} 
              className={`w-full border rounded-md px-3 py-2 ${errors.code ? 'border-red-500' : 'border-gray-300'}`} 
              placeholder="Ej. MAT-2026"
            />
            {errors.code && <p className="text-red-500 text-xs mt-1">{errors.code.message}</p>}
          </div>

          <div>
            <label className="block text-sm font-medium mb-1">Nombre</label>
            <input 
              {...register('name')} 
              className={`w-full border rounded-md px-3 py-2 ${errors.name ? 'border-red-500' : 'border-gray-300'}`} 
              placeholder="Ej. Matrícula"
            />
            {errors.name && <p className="text-red-500 text-xs mt-1">{errors.name.message}</p>}
          </div>

          <div>
            <label className="block text-sm font-medium mb-1">Monto (S/)</label>
            <input 
              {...register('amount')} 
              className={`w-full border rounded-md px-3 py-2 ${errors.amount ? 'border-red-500' : 'border-gray-300'}`} 
              placeholder="Ej. 150.00"
            />
            {errors.amount && <p className="text-red-500 text-xs mt-1">{errors.amount.message}</p>}
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium mb-1">Frecuencia</label>
              <select 
                {...register('recurrence')} 
                className="w-full border rounded-md px-3 py-2 border-gray-300"
              >
                <option value="single">Único</option>
                <option value="monthly">Mensual</option>
                <option value="annual">Anual</option>
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium mb-1">Día de vencimiento</label>
              <input 
                type="number"
                {...register('due_day', { valueAsNumber: true })} 
                className={`w-full border rounded-md px-3 py-2 ${errors.due_day ? 'border-red-500' : 'border-gray-300'}`} 
                placeholder="1 al 31"
              />
              {errors.due_day && <p className="text-red-500 text-xs mt-1">{errors.due_day.message}</p>}
            </div>
          </div>

          <div className="flex justify-end gap-3 mt-6">
            <button 
              type="button" 
              onClick={onCancel} 
              className="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md font-medium"
              disabled={isPending}
            >
              Cancelar
            </button>
            <button 
              type="submit" 
              className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md font-medium flex items-center"
              disabled={isPending}
            >
              {isPending ? 'Guardando...' : 'Guardar Concepto'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}
