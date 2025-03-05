import { defineStore } from "pinia";
import { computed, watch } from "vue";
import { TaskPlanService } from "../services";

export const useTaskPlanStore = defineStore("TaskPlanStore", {
    state: () => ({
        now: new Date(),
        names: [] as NameResult[],
        groups: [] as Group[],
        taskPlans: [] as TaskPlan[],
    }),
    actions: {
        init() {
            this.now = new Date();

            this.getTaskPlans();
            this.getGroups();
            this.getMembers();
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

        async updateTaskPlan(role: string, members: string, date: string) {
            TaskPlanService.updateTaskPlan(role, members, date).then(
                (result) => {
                    if (result) {
                        // const role = result.role;
                        // const plans = result.plans;
                        // this.taskPlans.find(item => item.role === role)!.plans = plans;
                    }
                }
            );
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
                return acc;
            }, {} as Record<string, TaskPlanFormItem>);
        },
    },
});
