const baseUrl = "/api/task-plan";

const routes = {
    plan: {
        index: baseUrl + "/plans",
        update: baseUrl + "/plans",
    },
    member: {
        index: baseUrl + "/members",
        delete: baseUrl + "/members/delete",
        update: baseUrl + "/members/update",
        create: baseUrl + "/members/create",
    },
    group: {
        index: baseUrl + "/groups",
        toggle: baseUrl + "/groups/roles/toggle",
    },
    
};

class RouteHelper {
    static replaceRoute(route: string, params: Object): string {
        for (let key in params) {
            route = route.replace(`:${key}`, params[key]);
        }
        return route;
    }
}

export { routes, RouteHelper };
