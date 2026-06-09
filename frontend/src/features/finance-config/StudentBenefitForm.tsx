import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { createStudentBenefitSchema, type CreateStudentBenefitInput } from './schemas'
import { useCreateStudentBenefit } from './hooks'

export function StudentBenefitForm({ onSuccess, onCancel }: { onSuccess: () => void; onCancel: () => void }) {
  const { register, handleSubmit, watch, formState: { errors } } = useForm<CreateStudentBenefitInput>({
    resolver: zodResolver(createStudentBenefitSchema),
    defaultValues: {
      benefit_type: 'percentage',
      stackable_with_early_payment: false
    }
  })
  
  const benefitType = watch('benefit_type')
  const { mutate, isPending, isError, error } = useCreateStudentBenefit()

  const onSubmit = (data: CreateStudentBenefitInput) => {
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
          <h2 className="text-xl font-bold">Nuevo Beneficio Estudiantil</h2>
        </div>
        
        <form onSubmit={handleSubmit(onSubmit)} className="p-6 space-y-4">
          {isError && (
            <div className="p-3 bg-red-50 text-red-700 rounded text-sm">
              {error instanceof Error ? error.message : 'Ocurrió un error al guardar.'}
            </div>
          )}

          <div>
            <label className="block text-sm font-medium mb-1">ID del Estudiante</label>
            <input 
              {...register('student_id')} 
              className={`w-full border rounded-md px-3 py-2 ${errors.student_id ? 'border-red-500' : 'border-gray-300'}`} 
              placeholder="UUID del estudiante"
            />
            {errors.student_id && <p className="text-red-500 text-xs mt-1">{errors.student_id.message}</p>}
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium mb-1">Tipo de beneficio</label>
              <select 
                {...register('benefit_type')} 
                className="w-full border rounded-md px-3 py-2 border-gray-300"
              >
                <option value="percentage">Descuento (%)</option>
                <option value="fixed">Monto Fijo</option>
                <option value="waiver">Exoneración Total</option>
              </select>
            </div>
            
            <div>
              <label className="block text-sm font-medium mb-1">Valor</label>
              <input 
                {...register('value')} 
                className={`w-full border rounded-md px-3 py-2 ${errors.value ? 'border-red-500' : 'border-gray-300'}`} 
                placeholder="Ej. 15.00"
                disabled={benefitType === 'waiver'}
              />
              {errors.value && <p className="text-red-500 text-xs mt-1">{errors.value.message}</p>}
            </div>
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium mb-1">Fecha de inicio</label>
              <input 
                type="date"
                {...register('starts_on')} 
                className={`w-full border rounded-md px-3 py-2 ${errors.starts_on ? 'border-red-500' : 'border-gray-300'}`} 
              />
              {errors.starts_on && <p className="text-red-500 text-xs mt-1">{errors.starts_on.message}</p>}
            </div>
            <div>
              <label className="block text-sm font-medium mb-1">Fecha de fin (Opcional)</label>
              <input 
                type="date"
                {...register('ends_on')} 
                className={`w-full border rounded-md px-3 py-2 ${errors.ends_on ? 'border-red-500' : 'border-gray-300'}`} 
              />
              {errors.ends_on && <p className="text-red-500 text-xs mt-1">{errors.ends_on.message}</p>}
            </div>
          </div>

          <div className="flex items-center gap-2 mt-4">
            <input 
              type="checkbox" 
              id="stackable" 
              {...register('stackable_with_early_payment')} 
              className="rounded text-blue-600"
            />
            <label htmlFor="stackable" className="text-sm font-medium">Acumulable con pronto pago</label>
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
              {isPending ? 'Guardando...' : 'Guardar Beneficio'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}
