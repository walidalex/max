<?php
declare(strict_types=1);
namespace App\Modules\CostStructure\Repositories;
use App\Core\Database\Database;
use App\Modules\CostStructure\DTOs\WorkSectionData;
final class WorkSectionRepository
{
    public function __construct(private readonly Database $db) {}
    public function all(): array { $r=$this->db->execute('SELECT id,section_code,name,description,sort_order,is_active FROM work_sections ORDER BY sort_order,section_code'); return $r instanceof \mysqli_result?$r->fetch_all(MYSQLI_ASSOC):[]; }
    public function find(int $id): ?array { $r=$this->db->execute('SELECT id,section_code,name,description,sort_order,is_active FROM work_sections WHERE id=? LIMIT 1',[$id]); $row=$r instanceof \mysqli_result?$r->fetch_assoc():null; return is_array($row)?$row:null; }
    public function codeExists(string $code, ?int $exceptId=null): bool { $sql='SELECT 1 FROM work_sections WHERE section_code=?'.($exceptId===null?'':' AND id<>?').' LIMIT 1'; $params=$exceptId===null?[$code]:[$code,$exceptId]; $r=$this->db->execute($sql,$params); return $r instanceof \mysqli_result&&$r->num_rows>0; }
    public function create(WorkSectionData $d): int { $this->db->execute('INSERT INTO work_sections(section_code,name,description,sort_order) VALUES(?,?,?,?)',[$d->sectionCode,$d->name,$d->description,$d->sortOrder]); return (int)$this->db->connection()->insert_id; }
    public function update(int $id, WorkSectionData $d): void { $this->db->execute('UPDATE work_sections SET section_code=?,name=?,description=?,sort_order=? WHERE id=?',[$d->sectionCode,$d->name,$d->description,$d->sortOrder,$id]); }
    public function setActive(int $id,bool $active): void { $this->db->execute('UPDATE work_sections SET is_active=? WHERE id=?',[$active?1:0,$id]); }
}
