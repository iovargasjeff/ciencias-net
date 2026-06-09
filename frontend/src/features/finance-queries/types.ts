export interface Receipt {
  id: string;
  description: string;
  amount: number;
  status: 'paid' | 'pending' | 'overdue';
  dueDate: string;
  paymentDate?: string;
}

export interface AccountStatement {
  studentId: string;
  studentName: string;
  grade: string;
  receipts: Receipt[];
  totalDue: number;
  earlyPaymentDiscount: {
    eligible: boolean;
    amount: number;
    deadline: string;
  } | null;
}

export interface Debtor {
  studentId: string;
  studentName: string;
  guardianName: string;
  guardianEmail: string;
  overdueAmount: number;
  overdueReceiptsCount: number;
  daysOverdue: number;
}

export interface CashReport {
  date: string;
  totalIncome: number;
  incomeByMethod: {
    method: 'cash' | 'transfer' | 'card';
    amount: number;
  }[];
  dailyData: {
    date: string;
    amount: number;
  }[];
}

export interface SendRemindersRequest {
  debtorIds: string[];
}
