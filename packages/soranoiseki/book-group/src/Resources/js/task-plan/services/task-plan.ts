import ApiClient from './api-client';
import { routes, RouteHelper } from './routes';

export class TaskPlanService {

    static getMembers(): Promise<NameResult[] | undefined> {
        const url = routes.member.index;
        return new Promise((resolve, reject) => {
            ApiClient.get<NameResult[]>(url, {})
                .then(result => resolve(result))
                .catch(error => reject(error));
        });
    }

    static deleteMember(name: string): Promise<NameResult[] | undefined> {
        const url = routes.member.delete;
        return new Promise((resolve, reject) => {
            ApiClient.post<NameResult[]>(url, { name })
                .then(result => resolve(result))
                .catch(error => reject(error));
        });
    }

    static getGroups(): Promise<Group[] | undefined> {
        const url = routes.group.index;
        return new Promise((resolve, reject) => {
            ApiClient.get<Group[]>(url, {})
                .then(result => resolve(result))
                .catch(error => reject(error));
        });
    }

    static toggleMemberRole(name: string, role: string): Promise<NameResult[] | undefined> {
        const url = routes.group.toggle;
        return new Promise((resolve, reject) => {
            ApiClient.post<NameResult[]>(url, { name, role })
                .then(result => resolve(result))
                .catch(error => reject(error));
        });
    }

    static getTaskPlans(): Promise<TaskPlan[] | undefined> {
        const url = routes.plan.index;
        return new Promise((resolve, reject) => {
            ApiClient.get<TaskPlan[]>(url, {})
                .then(result => resolve(result))
                .catch(error => reject(error));
        });
    }

    static updateTaskPlan(role: string, members: string, date: string): Promise<TaskPlan | undefined> {
        const url = routes.plan.update;
        return new Promise((resolve, reject) => {
            ApiClient.put<TaskPlan>(url, { role, members, date })
                .then(result => resolve(result))
                .catch(error => reject(error));
        });
    }
    
}
