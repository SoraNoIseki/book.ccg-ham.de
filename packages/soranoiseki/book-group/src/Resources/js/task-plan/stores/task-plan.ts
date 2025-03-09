import { defineStore } from "pinia";
import { computed, watch } from "vue";
import { TaskPlanService } from "../services";
import { DateTime } from "luxon";
import { useToast } from "vue-toast-notification";

const toastr = useToast();

export const useTaskPlanStore = defineStore("TaskPlanStore", {
    state: () => ({
        now: DateTime.now() as DateTime,
        names: [] as NameResult[],
        groups: [] as Group[],
        taskPlans: [] as TaskPlan[],
        groupFilter: [] as GroupFilterItem[],
        groupFilterInit: false,
        roles: [] as UserRole[],
        currentPlanMonth: DateTime.now() as DateTime,
        sundays: [] as DateTime[],
        abortController: null as AbortController | null,
        conflictMembers: [] as conflictMember[],
        loadingPlan: false,
    }),
    actions: {
        init() {
            // load from local storage
            const groupFilter = localStorage.getItem("groupFilter");
            if (groupFilter) {
                this.groupFilter = JSON.parse(groupFilter);
                this.groupFilterInit = true;
            }

            const currentPlanMonth = localStorage.getItem("currentPlanMonth");
            if (currentPlanMonth) {
                this.currentPlanMonth = DateTime.fromISO(currentPlanMonth);
            } else {
                this.currentPlanMonth = DateTime.now().startOf("month");
                this.saveCurrentPlanMonthToLocalStorage();
            }
            this.prepareSundays();

            // load user roles from window
            this.roles = JSON.parse((window as any).roles);

            // load data
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

                    const roleLabel = this.groupRoles.find((item) => item.role === role)?.name ?? role;
                    toastr.success(`组员 ${name} 已添加到 ${roleLabel}`);
                }
            });
        },

        async deleteMember(name: string) {
            TaskPlanService.deleteMember(name).then((result) => {
                if (result) {
                    this.names = result;

                    toastr.success(`组员 ${name} 已删除`);
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

        async toggleMemberRole(name: string, role: GroupRole) {
            TaskPlanService.toggleMemberRole(name, role.role).then((result) => {
                if (result) {
                    this.names = result;
                    toastr.success(`组员 ${name} 已更新：${role.name}`);
                }
            });
        },

        async getTaskPlans() {
            if (this.abortController) {
                this.abortController.abort();
            }

            this.abortController = new AbortController();
            const signal = this.abortController.signal;

            const year = this.currentPlanMonth.year.toString();
            const month = this.currentPlanMonth.month.toString();

            this.loadingPlan = true;
            TaskPlanService.getTaskPlans(year, month, signal).then((result) => {
                if (result) {
                    this.taskPlans = result;
                    this.updateConflictMembers();
                }
            }).catch(() => {
                this.loadingPlan = false
            }).finally(() => {
                this.abortController = null;
                this.loadingPlan = false;
            });
        },

        async updateTaskPlan(role: string, value: string, date: string) {
            TaskPlanService.updateTaskPlan(role, value, date).then((result) => {
                if (result) {
                    
                }
            });
        },

        updateLocalTaskPlan(role: string, plans: TaskPlanItem) {
            this.taskPlans.find(item => item.role === role)!.plans = plans;
            this.updateConflictMembers();
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

        nextPlanMonth() {
            this.currentPlanMonth = this.currentPlanMonth.plus({ months: 1 });
            this.updateAfterSwitchMonth();
        },

        prevPlanMonth() {
            this.currentPlanMonth = this.currentPlanMonth.minus({ months: 1 });
            this.updateAfterSwitchMonth();
        },

        backToCurrentMonth() {
            const currentMonth = DateTime.now().startOf("month");
            if (currentMonth.equals(this.currentPlanMonth)) {
                return;
            }

            this.currentPlanMonth = currentMonth;
            this.updateAfterSwitchMonth();
        },

        goToNextMonth() {
            const nextMonth = DateTime.now().startOf("month").plus({ months: 1 });
            if (nextMonth.equals(this.currentPlanMonth)) {
                return;
            }

            this.currentPlanMonth = nextMonth;
            this.updateAfterSwitchMonth();
        },

        updateAfterSwitchMonth() {
            this.saveCurrentPlanMonthToLocalStorage();
            this.prepareSundays();
            this.getTaskPlans();
        },

        saveCurrentPlanMonthToLocalStorage() {
            localStorage.setItem(
                "currentPlanMonth",
                this.currentPlanMonth.toISO() || DateTime.now().toISO()
            );
        },

        prepareSundays() {
            let sundays: DateTime[] = [];
            const currentMonth = this.currentPlanMonth;
            const firstDay = currentMonth.startOf("month");
            const lastDay = currentMonth.endOf("month");

            for (let i = 0; i < lastDay.day; i++) {
                const day = firstDay.plus({ days: i });
                if (day.weekday === 7) {
                    sundays.push(day);
                }
            }

            this.sundays = sundays;
        },

        updateConflictMembers() {
            const plans = this.taskPlans.filter((item) => item.role !== '主题');
            const conflictMembers: conflictMember[] = [];

            const weeks = [ "week1", "week2", "week3", "week4", "week5" ];
            for (const [index, week] of weeks.entries()) {
                const weekMembers = plans.reduce((acc, item) => {
                    if (!item.plans || !item.plans[week]) {
                        return acc;
                    }
                    const members: string[] = item.plans[week].split("+");
                    members.forEach((member) => {
                        if (!acc.find((item) => item.name === member)) {
                            acc.push({
                                name: member,
                                roles: [
                                    item.role,
                                ],
                            });
                        } else {
                            const target = acc.find((item) => item.name === member);
                            if (target) {
                                target.roles.push(item.role);
                            }
                        }
                    });
                    return acc;
                }, [] as GroupMember[]);

                const duplicated = weekMembers.filter(member => member.roles.length > 1);
                const date = this.sundays[index]?.toFormat('yyyy年M月d日') ?? '';
                if (duplicated.length > 0 && date !== '') {
                    duplicated.forEach((member) => {
                        conflictMembers.push({
                            name: member.name,
                            roles: member.roles,
                            date: date,
                        });
                    });
                }
            }

            this.conflictMembers = conflictMembers;
        },

        copyTaskPlanText(date: string) {
            let text: string = '';
            TaskPlanService.copyTaskPlanText(date).then((result) => {
                if (result) {
                    text = result.text;

                    // copy to clipboard
                    navigator.clipboard.writeText(text)
                        .then(() => toastr.success('服事安排已复制到剪切板'))
                        .catch(err => console.error("Failed to copy text: ", err));
                }
            });
        }
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
            if (TaskPlanService.isUserHasPermission("planer_admin")) {
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
        },
        groupRoles(): GroupRole[] {
            return this.groups.reduce((acc, group) => {
                return acc.concat(group.roles);
            }, [] as GroupRole[]);
        }
    },
});
