import ApiClient from "./api-client";
import { routes, RouteHelper } from "./routes";

export class TaskPlanService {
    static getMembers(): Promise<NameResult[] | undefined> {
        const url = routes.member.index;
        return new Promise((resolve, reject) => {
            ApiClient.get<NameResult[]>(url, {})
                .then((result) => resolve(result))
                .catch((error) => reject(error));
        });
    }

    static addMember(name: string, role: string): Promise<NameResult[] | undefined> {
        const url = routes.member.create;
        return new Promise((resolve, reject) => {
            ApiClient.post<NameResult[]>(url, { name, role })
                .then((result) => resolve(result))
                .catch((error) => reject(error));
        });
    }

    static deleteMember(name: string): Promise<NameResult[] | undefined> {
        const url = routes.member.delete;
        return new Promise((resolve, reject) => {
            ApiClient.post<NameResult[]>(url, { name })
                .then((result) => resolve(result))
                .catch((error) => reject(error));
        });
    }

    static getGroups(): Promise<Group[] | undefined> {
        const url = routes.group.index;
        return new Promise((resolve, reject) => {
            ApiClient.get<Group[]>(url, {})
                .then((result) => resolve(result))
                .catch((error) => reject(error));
        });
    }

    static toggleMemberRole(
        name: string,
        role: string
    ): Promise<NameResult[] | undefined> {
        const url = routes.group.toggle;
        return new Promise((resolve, reject) => {
            ApiClient.post<NameResult[]>(url, { name, role })
                .then((result) => resolve(result))
                .catch((error) => reject(error));
        });
    }

    static getTaskPlans(year: string, month: string, signal: AbortSignal): Promise<TaskPlan[] | undefined> {
        const url = routes.plan.index;
        return new Promise((resolve, reject) => {
            ApiClient.get<TaskPlan[]>(url, { year, month }, signal)
                .then((result) => resolve(result))
                .catch((error) => reject(error));
        });
    }

    static updateTaskPlan(
        role: string,
        value: string,
        date: string
    ): Promise<TaskPlan | undefined> {
        const url = routes.plan.update;
        return new Promise((resolve, reject) => {
            ApiClient.put<TaskPlan>(url, { role, value, date })
                .then((result) => resolve(result))
                .catch((error) => reject(error));
        });
    }

    static isUserHasPermission (permission: string) {
        const userRolesJson = (window as any).roles;
        if (userRolesJson && userRolesJson.length > 0) {
            const userRoles = JSON.parse(userRolesJson) as UserRole[];
            return userRoles.filter((role: UserRole) => role.internal_name === permission || role.internal_name === 'planer_admin').length > 0;
        }
    
        return false;
    };

    static isUserCanDeleteMember(member: GroupMember, groups: Group[]): Group[] | boolean {
        if (this.isUserHasPermission('planer_admin')) {
            return true;
        }

        if (groups.length === 0) {
            return false;
        }
        
        let notAllowedGroups: Group[] = [];
        member.roles.forEach((role: string) => {
            const group = groups.find((group: Group) => group.roles.filter((groupRole: GroupRole) => groupRole.role === role).length > 0);
            if (group && !this.isUserHasPermission(group.permission)) {
                notAllowedGroups.push(group);
            }
        });

        if (notAllowedGroups.length === 0) {
            return true;
        }

        return notAllowedGroups;
    }
}
