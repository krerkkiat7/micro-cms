<?php

namespace App\Support;

use App\Models\PageItemColumn;
use App\Models\PageItemColumnDetail;
use App\Models\PageItemInfo;
use App\Models\PageItemRow;
use App\Models\PageItemRowDetail;
use App\Models\PageItemWidget;
use App\Models\PageItemWidgetDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * บันทึกโครงสร้างทั้งหน้า (แถว → คอลัมน์ → widget) ที่ส่งมาจากหน้า "โครงสร้าง" ของโมดูล Page
 *
 * ต่างจากปุ่มของ Intropage ที่ลบแล้วสร้างใหม่ทั้งชุด — ที่นี่ต้อง "คง id เดิมไว้" เพราะ widget จะอ้างไฟล์/ข้อมูลอื่นในอนาคต
 * และตรวจสอบตรง ๆ ได้ง่าย: รายการที่มี id ของหน้านี้ = update, ไม่มี id = สร้างใหม่, id เดิมที่ไม่อยู่ในข้อมูลที่ส่งมาแล้ว =
 * soft delete (เทียบทั้งหน้าในแต่ละชั้น ไม่ผูกกับพาเรนต์เดิม จึงรองรับ widget ที่ย้ายข้ามคอลัมน์ได้ และลูกของแถวที่ถูกลบ
 * ถูกลบตามไปเองเพราะไม่มีอยู่ในข้อมูลที่ส่งมา) ลำดับ `sort_order` = ตำแหน่งใน array ที่ส่งมา
 *
 * ข้อมูลแยกภาษา (*_detail) ใช้ where(id, lang) เสมอ ห้าม save() ผ่าน model เพราะ composite key (ดู IntropageItemController)
 * ตรวจสอบว่า id เป็นของหน้านี้แล้วที่ UpdatePageItemLayoutRequest
 */
class PageLayoutSync
{
    private const BACKGROUND_FIELDS = [
        'background_color', 'background_image_id', 'background_repeat',
        'background_size', 'background_attachment', 'background_position',
    ];

    /** @var list<int> */
    private array $keptRowIds = [];

    /** @var list<int> */
    private array $keptColumnIds = [];

    /** @var list<int> */
    private array $keptWidgetIds = [];

    public function __construct(private readonly PageItemInfo $page, private readonly int $actorId) {}

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function sync(array $rows): void
    {
        DB::transaction(function () use ($rows) {
            $existingRowIds = PageItemRow::where('page_item_info_id', $this->page->id)->pluck('id')->map(fn ($id) => (int) $id)->all();
            $existingColumnIds = PageItemColumn::whereIn('page_item_row_id', $existingRowIds)->pluck('id')->map(fn ($id) => (int) $id)->all();
            $existingWidgetIds = PageItemWidget::whereIn('page_item_column_id', $existingColumnIds)->pluck('id')->map(fn ($id) => (int) $id)->all();

            foreach (array_values($rows) as $rowIndex => $rowData) {
                $row = $this->upsert(PageItemRow::class, $existingRowIds, $rowData, [
                    'page_item_info_id' => $this->page->id,
                    'sort_order' => $rowIndex,
                    'show_title' => $rowData['show_title'],
                    'use_container' => $rowData['use_container'],
                    'status' => $rowData['status'],
                ] + $this->background($rowData));
                $this->keptRowIds[] = $row->id;
                $this->syncDetails(PageItemRowDetail::class, $row->id, $rowData['detail'] ?? []);

                foreach (array_values($rowData['columns'] ?? []) as $columnIndex => $columnData) {
                    $column = $this->upsert(PageItemColumn::class, $existingColumnIds, $columnData, [
                        'page_item_row_id' => $row->id,
                        'sort_order' => $columnIndex,
                        'show_title' => $columnData['show_title'],
                        'column_size' => $columnData['column_size'],
                        'status' => $columnData['status'],
                    ] + $this->background($columnData));
                    $this->keptColumnIds[] = $column->id;
                    $this->syncDetails(PageItemColumnDetail::class, $column->id, $columnData['detail'] ?? []);

                    foreach (array_values($columnData['widgets'] ?? []) as $widgetIndex => $widgetData) {
                        $widget = $this->upsert(PageItemWidget::class, $existingWidgetIds, $widgetData, [
                            'page_item_column_id' => $column->id,
                            'sort_order' => $widgetIndex,
                            'show_title' => $widgetData['show_title'],
                            'widget_type' => $widgetData['widget_type'],
                            'setting' => $widgetData['setting'] ?? null,
                            'status' => $widgetData['status'],
                        ]);
                        $this->keptWidgetIds[] = $widget->id;
                        $this->syncDetails(PageItemWidgetDetail::class, $widget->id, $widgetData['detail'] ?? []);
                    }
                }
            }

            $this->softDeleteMissing(PageItemWidget::class, $existingWidgetIds, $this->keptWidgetIds);
            $this->softDeleteMissing(PageItemColumn::class, $existingColumnIds, $this->keptColumnIds);
            $this->softDeleteMissing(PageItemRow::class, $existingRowIds, $this->keptRowIds);

            $this->page->forceFill([
                'layout_updated_at' => now(),
                'layout_updated_by' => $this->actorId,
            ])->save();
        });
    }

    /**
     * update แถวเดิม (ถ้ามี id ที่เป็นของหน้านี้) หรือสร้างใหม่
     *
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $model
     * @param  list<int>  $existingIds
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $attributes
     * @return TModel
     */
    private function upsert(string $model, array $existingIds, array $data, array $attributes): Model
    {
        $id = $data['id'] ?? null;

        if ($id !== null && in_array((int) $id, $existingIds, true)) {
            $record = $model::findOrFail($id);
            $record->fill($attributes + ['updated_by' => $this->actorId])->save();

            return $record;
        }

        return $model::create($attributes + ['created_by' => $this->actorId]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function background(array $data): array
    {
        $values = [];

        foreach (self::BACKGROUND_FIELDS as $field) {
            $values[$field] = $data[$field] ?? null;
        }

        return $values;
    }

    /**
     * บันทึกข้อมูลแยกภาษาของ 1 รายการ — เช็กว่ามีแถวอยู่แล้วก่อนแล้วค่อย update/create (ไม่ใช้จำนวนแถวที่ update เป็นตัวตัดสิน
     * เพราะบันทึกซ้ำด้วยค่าเดิมในวินาทีเดียวกันจะได้ 0 แล้วไป create ชน PK)
     *
     * @param  class-string<Model>  $model
     * @param  array<string, array<string, mixed>>  $details
     */
    private function syncDetails(string $model, int $id, array $details): void
    {
        foreach ($details as $lang => $detail) {
            $values = [
                'title' => $detail['title'] ?? null,
                'intro_text' => $detail['intro_text'] ?? null,
            ];
            $query = $model::where('id', $id)->where('lang', $lang);

            if ($query->exists()) {
                $query->update($values + ['updated_by' => $this->actorId]);
            } else {
                $model::create(['id' => $id, 'lang' => $lang, 'status' => 'Y', 'created_by' => $this->actorId] + $values);
            }
        }
    }

    /**
     * soft delete รายการเดิมที่ไม่อยู่ในข้อมูลที่ส่งมาแล้ว (เก็บ deleted_by ด้วย)
     *
     * @param  class-string<Model>  $model
     * @param  list<int>  $existingIds
     * @param  list<int>  $keptIds
     */
    private function softDeleteMissing(string $model, array $existingIds, array $keptIds): void
    {
        $missing = array_values(array_diff($existingIds, $keptIds));

        if ($missing !== []) {
            $model::whereIn('id', $missing)->update(['deleted_by' => $this->actorId, 'deleted_at' => now()]);
        }
    }
}
