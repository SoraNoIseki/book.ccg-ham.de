import { defineStore } from "pinia";
import { computed, watch } from "vue";
import { TaskPlanService } from "../services";
import { json } from "stream/consumers";

export const useTaskPlanStore = defineStore("TaskPlanStore", {
    state: () => ({
        now: new Date(),
        names: [] as NameResult[],
        groups: [] as Group[],
        taskPlans: [] as TaskPlan[],
        groupFilter: [] as GroupFilterItem[],
        groupFilterInit: false,
        roles: [] as UserRole[],
    }),
    actions: {
        init() {
            this.now = new Date();

            this.getTaskPlans();
            this.getGroups();
            this.getMembers();

            // load from local storage
            const groupFilter = localStorage.getItem("groupFilter");
            if (groupFilter) {
                this.groupFilter = JSON.parse(groupFilter);
                this.groupFilterInit = true;
            }

            // load user roles from window
            this.roles = JSON.parse((window as any).roles);
        },

        async getMembers() {
            TaskPlanService.getMembers().then((result) => {
                if (result) {
                    this.names = result;
                }
            });
        },

        async addMember(name: string, role: string) {
            TaskPlanService.addMember(name, role).then((result) => {
                if (result) {
                    this.names = result;
                }
            });
        },

        async deleteMember(name: string) {
            TaskPlanService.deleteMember(name).then((result) => {
                if (result) {
                    this.names = result;
                }
            });
        },

        async getGroups() {
            TaskPlanService.getGroups().then((result) => {
                if (result) {
                    this.groups = result;
                    if (!this.groupFilterInit) {
                        this.initGroupFilter();
                    }
                }
            });
        },

        async toggleMemberRole(name: string, role: string) {
            TaskPlanService.toggleMemberRole(name, role).then((result) => {
                if (result) {
                    this.names = result;
                }
            });
        },

        async getTaskPlans() {
            TaskPlanService.getTaskPlans().then((result) => {
                if (result) {
                    this.taskPlans = result;
                }
            });
        },

        async updateTaskPlan(role: string, value: string, date: string) {
            TaskPlanService.updateTaskPlan(role, value, date).then((result) => {
                if (result) {
                    // const role = result.role;
                    // const plans = result.plans;
                    // this.taskPlans.find(item => item.role === role)!.plans = plans;
                }
            });
        },

        initGroupFilter() {
            this.groupFilter = this.groups.map((group) => {
                return {
                    name: group.group,
                    color: group.color,
                    enabled: true,
                };
            });
        },
    },
    getters: {
        groupMembers(): GroupMember[] {
            const nameMap: Record<string, string[]> = {};

            for (const entry of this.names) {
                for (const name of entry.names) {
                    if (!nameMap[name]) {
                        nameMap[name] = [];
                    }
                    nameMap[name].push(entry.role);
                }
            }

            return Object.entries(nameMap)
                .map(([name, roles]) => ({ name, roles }))
                .sort((a, b) => a.name.localeCompare(b.name, "zh-CN"));
        },
        sortedMembersByRole(): NameResult[] {
            return this.names.map((entry) => ({
                // sort by names
                ...entry,
                names: entry.names.sort((a, b) => a.localeCompare(b, "zh-CN")),
            }));
        },
        planForm(): Record<string, TaskPlanFormItem> {
            return this.taskPlans.reduce((acc, item) => {
                const role = item.role;
                let type = "select";
                this.groups.forEach((group) => {
                    group.roles.forEach((groupRole) => {
                        if (groupRole.role === role) {
                            type = groupRole.type;
                        }
                    });
                });

                if (type === "select") {
                    acc[item.role] = item.plans
                        ? {
                              week1: item.plans.week1.split("+"),
                              week2: item.plans.week2.split("+"),
                              week3: item.plans.week3.split("+"),
                              week4: item.plans.week4.split("+"),
                              week5: item.plans.week5.split("+"),
                          }
                        : {
                              week1: [],
                              week2: [],
                              week3: [],
                              week4: [],
                              week5: [],
                          };
                } else {
                    acc[item.role] = item.plans
                        ? {
                              week1: item.plans.week1,
                              week2: item.plans.week2,
                              week3: item.plans.week3,
                              week4: item.plans.week4,
                              week5: item.plans.week5,
                          }
                        : {
                              week1: "",
                              week2: "",
                              week3: "",
                              week4: "",
                              week5: "",
                          };
                }

                return acc;
            }, {} as Record<string, TaskPlanFormItem>);
        },
        isUserHasEditPermission(): boolean {
            if (TaskPlanService.isUserHasPermission('planer_admin')) {
                return true;
            }
        
            let hasPermission = false;
            this.groups.forEach((group: Group) => {
                if (TaskPlanService.isUserHasPermission(group.permission)) {
                    hasPermission = true;
                    return;
                }
            });
            return hasPermission;
        }
    },
});
