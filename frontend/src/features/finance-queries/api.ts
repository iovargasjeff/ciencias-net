import type { AccountStatement, Debtor, CashReport, SendRemindersRequest } from './types';

const delay = (ms: number) => new Promise(resolve => setTimeout(resolve, ms));

const MOCK_STATEMENTS: AccountStatement[] = [
  {
    studentId: 'STU-001',
    studentName: 'Ana García',
    grade: '1ro Secundaria',
    totalDue: 350.00,
    earlyPaymentDiscount: {
      eligible: true,
      amount: 50.00,
      deadline: '2023-11-10',
    },
    receipts: [
      { id: 'REC-001', description: 'Matrícula 2023', amount: 300.00, status: 'paid', dueDate: '2023-02-15', paymentDate: '2023-02-10' },
      { id: 'REC-002', description: 'Pensión Marzo', amount: 350.00, status: 'paid', dueDate: '2023-03-31', paymentDate: '2023-03-25' },
      { id: 'REC-003', description: 'Pensión Abril', amount: 350.00, status: 'pending', dueDate: '2023-04-30' },
    ]
  },
  {
    studentId: 'STU-002',
    studentName: 'Carlos García',
    grade: '3ro Secundaria',
    totalDue: 0,
    earlyPaymentDiscount: null,
    receipts: [
      { id: 'REC-004', description: 'Matrícula 2023', amount: 300.00, status: 'paid', dueDate: '2023-02-15', paymentDate: '2023-02-15' },
      { id: 'REC-005', description: 'Pensión Marzo', amount: 350.00, status: 'paid', dueDate: '2023-03-31', paymentDate: '2023-03-28' },
    ]
  }
];

const MOCK_DEBTORS: Debtor[] = [
  { studentId: 'STU-010', studentName: 'Luis Pérez', guardianName: 'Juan Pérez', guardianEmail: 'juan.perez@email.com', overdueAmount: 700.00, overdueReceiptsCount: 2, daysOverdue: 45 },
  { studentId: 'STU-011', studentName: 'María Gómez', guardianName: 'Rosa Gómez', guardianEmail: 'rosa.gomez@email.com', overdueAmount: 350.00, overdueReceiptsCount: 1, daysOverdue: 15 },
];

const MOCK_CASH_REPORT: CashReport = {
  date: new Date().toISOString().split('T')[0],
  totalIncome: 15400.00,
  incomeByMethod: [
    { method: 'cash', amount: 3400.00 },
    { method: 'transfer', amount: 8000.00 },
    { method: 'card', amount: 4000.00 },
  ],
  dailyData: [
    { date: '2023-10-01', amount: 1200 },
    { date: '2023-10-02', amount: 2300 },
    { date: '2023-10-03', amount: 800 },
    { date: '2023-10-04', amount: 3100 },
    { date: '2023-10-05', amount: 1500 },
    { date: '2023-10-06', amount: 4500 },
    { date: '2023-10-07', amount: 2000 },
  ]
};

export const financeQueriesApi = {
  listAccountStatements: async (): Promise<AccountStatement[]> => {
    await delay(800);
    return MOCK_STATEMENTS;
  },

  listDebtors: async (): Promise<Debtor[]> => {
    await delay(800);
    return MOCK_DEBTORS;
  },

  getCashReport: async (): Promise<CashReport> => {
    await delay(1000);
    if (localStorage.getItem('force_cash_report_error') === 'true') {
      throw new Error('Error simulado al obtener el reporte de caja.');
    }
    return MOCK_CASH_REPORT;
  },

  sendPaymentReminders: async (data: SendRemindersRequest): Promise<void> => {
    await delay(1200);
    console.log(`Reminders sent to ${data.debtorIds.length} debtors.`);
  }
};
