const routes = {
    plan: {
        index: "/plans",
        update: "/plans",
    },
    member: {
        index: "/members",
        create: "/members",
        delete: "/members/delete",
    },
    group: {
        index: "/groups",
        toggle: "/groups/roles/toggle",
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
