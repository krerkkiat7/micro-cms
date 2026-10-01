<script setup lang="ts">
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import TabNav from '@/Components/Admin/TabNav.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import PermissionTreeNode from '@/Components/Admin/PermissionTreeNode.vue';
import BackToListButton from '@/Components/Admin/BackToListButton.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ChevronDown, Save } from 'lucide-vue-next';
import { computed, reactive } from 'vue';
import type { ActionGroupNode, ActionNode } from '@/types';

const props = defineProps<{
    group: { id: number; name: string; can_edit: string };
    /** กลุ่มของผู้ใช้ปัจจุบันเอง — ดูได้อย่างเดียว */
    isOwnGroup: boolean;
    actionGroups: ActionGroupNode[];
    checkedIds: string[];
}>();

const readOnly = computed(() => props.group.can_edit === 'N' || props.isOwnGroup);

// ---- การเลือกสิทธิ์ (reactive Set) ----
const selected = reactive(new Set<string>(props.checkedIds));

function isSelected(id: string): boolean {
    return selected.has(id);
}

function flatten(nodes: ActionNode[]): ActionNode[] {
    return nodes.flatMap((n) => [n, ...flatten(n.children)]);
}

function descendantIds(node: ActionNode): string[] {
    return flatten(node.children).map((n) => n.id);
}

function onToggle(node: ActionNode, checked: boolean) {
    if (readOnly.value) return;

    if (checked) {
        selected.add(node.id);
    } else {
        selected.delete(node.id);
        // ปิด parent = ล้างลูกทั้งหมด
        for (const id of descendantIds(node)) selected.delete(id);
    }
}

// ---- ระดับกลุ่มสิทธิ์ ----
function groupActionIds(g: ActionGroupNode): string[] {
    return flatten(g.actions).map((n) => n.id);
}

function groupSelectedCount(g: ActionGroupNode): number {
    return groupActionIds(g).filter((id) => selected.has(id)).length;
}

function selectAll(g: ActionGroupNode) {
    if (readOnly.value) return;
    for (const id of groupActionIds(g)) selected.add(id);
}

function deselectAll(g: ActionGroupNode) {
    if (readOnly.value) return;
    for (const id of groupActionIds(g)) selected.delete(id);
}

// ---- เปิด/ปิดกลุ่มสิทธิ์ (จำสถานะเหมือนเมนูข้าง) ----
const STORAGE_PREFIX = 'admin.usergroup.rights.group.';

function readOpen(id: string): boolean {
    try {
        const v = window.localStorage.getItem(STORAGE_PREFIX + id);
        return v === null ? true : v === '1';
    } catch {
        return true;
    }
}

const open = reactive<Record<string, boolean>>(
    Object.fromEntries(props.actionGroups.map((g) => [g.id, readOpen(g.id)])),
);

function toggleOpen(id: string) {
    open[id] = !open[id];
    try {
        window.localStorage.setItem(STORAGE_PREFIX + id, open[id] ? '1' : '0');
    } catch {
        /* storage ปิด — ข้ามได้ */
    }
}

// ---- บันทึก ----
const form = useForm<{ action_ids: string[] }>({ action_ids: [] });

function submit() {
    form
        .transform(() => ({ action_ids: [...selected] }))
        .put(route('admin.system.usergroup.rights.update', props.group.id));
}

const breadcrumbs = computed(() => [
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'จัดการกลุ่มผู้ใช้งาน', href: route('admin.system.usergroup.index') },
    {
        label: props.group.name,
        href: route('admin.system.usergroup.edit', props.group.id),
    },
    { label: 'กำหนดสิทธิ์' },
]);

const tabs = computed(() => [
    {
        label: 'ข้อมูลทั่วไป',
        href: route('admin.system.usergroup.edit', props.group.id),
        active: false,
    },
    {
        label: 'กำหนดสิทธิ์',
        href: route('admin.system.usergroup.rights', props.group.id),
        active: true,
    },
]);
</script>

<template>
    <Head :title="`กำหนดสิทธิ์: ${group.name}`" />

    <AdminLayout>
        <template #header>
            <PageHeader title="กำหนดสิทธิ์" :breadcrumbs="breadcrumbs" />
        </template>

        <div class="space-y-6">
            <TabNav :tabs="tabs" />

            <form class="space-y-4" @submit.prevent="submit">
                <div
                    class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs sm:p-6"
                >
                    <p class="text-sm text-gray-500">
                        กำหนดสิทธิ์ให้กลุ่ม
                        <span class="font-semibold text-gray-800">
                            {{ group.name }}
                        </span>
                    </p>
                    <p v-if="group.can_edit === 'N'" class="mt-1 text-sm text-amber-600">
                        กลุ่มนี้เป็นกลุ่มระบบ แสดงอย่างเดียว ไม่สามารถแก้ไขได้
                    </p>
                    <p v-else-if="isOwnGroup" class="mt-1 text-sm text-amber-600">
                        กลุ่มนี้เป็นกลุ่มของคุณเอง แสดงอย่างเดียว — การกำหนดสิทธิ์ให้กลุ่มของตัวเองต้องให้ผู้ใช้งานกลุ่มอื่นที่มีสิทธิ์เป็นผู้ดำเนินการ
                    </p>
                </div>

                <div
                    v-for="g in actionGroups"
                    :key="g.id"
                    class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xs"
                >
                    <div
                        class="flex flex-wrap items-center gap-x-3 gap-y-1 border-b border-gray-100 bg-gray-50 px-4 py-3"
                    >
                        <button
                            type="button"
                            class="flex cursor-pointer items-center gap-2 text-sm font-semibold text-gray-800 hover:text-gray-900"
                            @click="toggleOpen(g.id)"
                        >
                            <ChevronDown
                                class="size-4 shrink-0 text-gray-400 transition-transform"
                                :class="open[g.id] ? '' : '-rotate-90'"
                            />
                            {{ g.name }}
                            <span class="font-normal text-gray-500">
                                ({{ groupSelectedCount(g) }}/{{ g.total }})
                            </span>
                        </button>

                        <div
                            v-if="!readOnly"
                            class="ml-auto flex items-center gap-3 text-xs"
                        >
                            <button
                                type="button"
                                class="cursor-pointer font-medium text-brand-600 hover:text-brand-700"
                                @click="selectAll(g)"
                            >
                                เลือกทั้งหมด
                            </button>
                            <span class="text-gray-300">|</span>
                            <button
                                type="button"
                                class="cursor-pointer font-medium text-gray-500 hover:text-gray-700"
                                @click="deselectAll(g)"
                            >
                                ไม่เลือกทั้งหมด
                            </button>
                        </div>
                    </div>

                    <div v-show="open[g.id]" class="px-4 py-3">
                        <PermissionTreeNode
                            v-for="node in g.actions"
                            :key="node.id"
                            :node="node"
                            :parent-selected="true"
                            :read-only="readOnly"
                            :is-selected="isSelected"
                            @toggle="onToggle"
                        />
                    </div>
                </div>

                <InputError :message="form.errors.action_ids" />

                <div class="flex flex-wrap items-center gap-3">
                    <PrimaryButton v-if="!readOnly" type="submit" :disabled="form.processing">
                        <Save class="mr-1.5 size-4" /> บันทึก
                    </PrimaryButton>
                    <BackToListButton :href="route('admin.system.usergroup.index')" />
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
