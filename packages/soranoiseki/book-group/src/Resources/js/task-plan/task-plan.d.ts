interface SuccessApiResponse<T> {
    success: true;
    massage: null | string;
    data: T;
}

interface ErrorApiResponse {
    success: false;
    status: "error";
    message: string;
}

interface FailApiResponse {
    success: false;
    data: Record<string, string[]>;
    status: "fail";
}

// Error code 500
interface ServerErrorApiResponse {
    message: string;
}

type ApiResponse<T> = SuccessApiResponse<T> | ErrorApiResponse;

interface SelectOption {
    value: string | number;
    label: string;
}

interface SelectOptionGroup {
    label: string;
    options: SelectOption[];
}

interface NameResult {
    role: string;
    names: string[];
}

interface GroupMember {
    name: string;
    roles: string[];
}

interface GroupRole {
    name: string;
    role: string;
    type: 'input' | 'select';
    max?: number;
    placeholder?: string;
}

interface Group {
    group: string;
    color: string;
    permission: string;
    roles: GroupRole[];
}

interface TaskPlan {
    role: string;
    plans: TaskPlanItem;
}

interface TaskPlanItem {
    group_id: string;
    week1: string;
    week2: string;
    week3: string;
    week4: string;
    week5: string;
}

interface TaskPlanFormItem {
    week1: string[] | string;
    week2: string[] | string;
    week3: string[] | string;
    week4: string[] | string;
    week5: string[] | string;
}

interface GroupFilterItem {
    name: string;
    color: string;
    enabled: boolean;
}

interface UserRole {
    name: string;
    internal_name: string;
}