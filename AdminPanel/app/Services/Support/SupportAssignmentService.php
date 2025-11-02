<?php

namespace App\Services\Support;

use App\Models\SupportConversation;
use App\Models\Employee;
use App\Models\CustomerSupportMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * Support Assignment Service
 * 
 * Logic thông minh để phân công cuộc hội thoại cho nhân viên support
 */
class SupportAssignmentService
{
    /**
     * Tự động assign cuộc hội thoại cho nhân viên rảnh nhất
     * 
     * Logic ưu tiên:
     * 1. Nhân viên có role "Support Staff" hoặc "Staff"
     * 2. Nhân viên đang online (optional)
     * 3. Nhân viên có số conversation đang xử lý ít nhất
     * 4. Nhân viên có rating cao nhất (optional)
     * 
     * @param SupportConversation $conversation
     * @return Employee|null
     */
    public function autoAssignToAvailableEmployee(SupportConversation $conversation): ?Employee
    {
        try {
            // Lấy danh sách nhân viên có thể assign
            $availableEmployees = Employee::where('role', 'Staff') // hoặc 'Support Staff'
                ->where('isActive', 1) // Giả sử có field isActive
                ->get();

            if ($availableEmployees->isEmpty()) {
                \Log::warning('⚠️ No available employees for assignment');
                return null;
            }

            // Tính toán workload cho mỗi nhân viên
            $employeesWithWorkload = $availableEmployees->map(function ($employee) {
                $activeConversations = SupportConversation::where('assigned_employee_id', $employee->employeeID)
                    ->whereIn('status', ['open', 'pending'])
                    ->count();

                return [
                    'employee' => $employee,
                    'workload' => $activeConversations,
                ];
            });

            // Sắp xếp theo workload thấp nhất
            $sortedEmployees = $employeesWithWorkload->sortBy('workload');

            // Chọn nhân viên đầu tiên (ít công việc nhất)
            $selectedEmployee = $sortedEmployees->first()['employee'];

            // Assign conversation
            $conversation->assignTo($selectedEmployee->employeeID);

            \Log::info("✅ Auto-assigned conversation to employee", [
                'conversation_id' => $conversation->conversation_id,
                'employee_id' => $selectedEmployee->employeeID,
                'employee_name' => $selectedEmployee->fullName,
                'workload' => $sortedEmployees->first()['workload']
            ]);

            return $selectedEmployee;

        } catch (\Exception $e) {
            \Log::error('❌ Auto assignment error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Phân công lại khi nhân viên hiện tại không phản hồi
     * 
     * @param SupportConversation $conversation
     * @param int|null $excludeEmployeeId Loại trừ nhân viên này
     * @return Employee|null
     */
    public function reassignToAnotherEmployee(SupportConversation $conversation, ?int $excludeEmployeeId = null): ?Employee
    {
        try {
            $query = Employee::where('role', 'Staff')
                ->where('isActive', 1);

            if ($excludeEmployeeId) {
                $query->where('employeeID', '!=', $excludeEmployeeId);
            }

            $availableEmployees = $query->get();

            if ($availableEmployees->isEmpty()) {
                \Log::warning('⚠️ No other employees available for reassignment');
                return null;
            }

            // Logic tương tự autoAssignToAvailableEmployee
            $employeesWithWorkload = $availableEmployees->map(function ($employee) {
                $activeConversations = SupportConversation::where('assigned_employee_id', $employee->employeeID)
                    ->whereIn('status', ['open', 'pending'])
                    ->count();

                return [
                    'employee' => $employee,
                    'workload' => $activeConversations,
                ];
            });

            $sortedEmployees = $employeesWithWorkload->sortBy('workload');
            $selectedEmployee = $sortedEmployees->first()['employee'];

            // Reassign
            $conversation->assignTo($selectedEmployee->employeeID);

            \Log::info("🔄 Reassigned conversation", [
                'conversation_id' => $conversation->conversation_id,
                'from_employee_id' => $excludeEmployeeId,
                'to_employee_id' => $selectedEmployee->employeeID
            ]);

            return $selectedEmployee;

        } catch (\Exception $e) {
            \Log::error('❌ Reassignment error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Lấy thống kê workload của tất cả nhân viên support
     * 
     * @return array
     */
    public function getEmployeesWorkload(): array
    {
        try {
            $employees = Employee::where('role', 'Staff')
                ->get();

            $workload = $employees->map(function ($employee) {
                $stats = [
                    'employee_id' => $employee->employeeID,
                    'full_name' => $employee->fullName,
                    'total_conversations' => 0,
                    'open_conversations' => 0,
                    'pending_conversations' => 0,
                    'closed_today' => 0,
                    'avg_response_time' => 0, // Seconds
                ];

                // Đếm conversations
                $stats['total_conversations'] = SupportConversation::where('assigned_employee_id', $employee->employeeID)
                    ->count();

                $stats['open_conversations'] = SupportConversation::where('assigned_employee_id', $employee->employeeID)
                    ->where('status', 'open')
                    ->count();

                $stats['pending_conversations'] = SupportConversation::where('assigned_employee_id', $employee->employeeID)
                    ->where('status', 'pending')
                    ->count();

                $stats['closed_today'] = SupportConversation::where('assigned_employee_id', $employee->employeeID)
                    ->where('status', 'closed')
                    ->whereDate('updated_at', today())
                    ->count();

                // Tính avg response time (optional - cần thêm logic phức tạp)
                // $stats['avg_response_time'] = $this->calculateAvgResponseTime($employee->employeeID);

                return $stats;
            });

            return $workload->toArray();

        } catch (\Exception $e) {
            \Log::error('❌ Get workload error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Kiểm tra xem có cần reassign không
     * (Nếu nhân viên không phản hồi sau X phút)
     * 
     * @param SupportConversation $conversation
     * @param int $timeoutMinutes
     * @return bool
     */
    public function shouldReassign(SupportConversation $conversation, int $timeoutMinutes = 10): bool
    {
        if (!$conversation->assigned_employee_id) {
            return false;
        }

        // Lấy tin nhắn cuối từ customer
        $lastCustomerMessage = CustomerSupportMessage::where('conversation_id', $conversation->conversation_id)
            ->where('sender_type', 'customer')
            ->latest('created_at')
            ->first();

        if (!$lastCustomerMessage) {
            return false;
        }

        // Kiểm tra xem có tin nhắn phản hồi từ employee sau đó không
        $employeeResponse = CustomerSupportMessage::where('conversation_id', $conversation->conversation_id)
            ->where('sender_type', 'employee')
            ->where('created_at', '>', $lastCustomerMessage->created_at)
            ->exists();

        if ($employeeResponse) {
            return false; // Đã có phản hồi
        }

        // Kiểm tra timeout
        $minutesSinceLastMessage = $lastCustomerMessage->created_at->diffInMinutes(now());

        return $minutesSinceLastMessage >= $timeoutMinutes;
    }

    /**
     * Cache key cho workload
     */
    private function getWorkloadCacheKey(): string
    {
        return 'support:employees:workload';
    }

    /**
     * Clear cache workload
     */
    public function clearWorkloadCache(): void
    {
        Cache::forget($this->getWorkloadCacheKey());
    }
}