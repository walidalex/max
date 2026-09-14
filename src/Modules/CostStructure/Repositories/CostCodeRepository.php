<?php
declare(strict_types=1);
namespace App\Modules\CostStructure\Repositories;
use App\Core\Database\Database;
use App\Modules\CostStructure\DTOs\CostCodeData;
final class CostCodeRepository
{
    public function __construct(private readonly Database $db) {}
    public function all(): array { $sql='SELECT c.id,c.work_section_id,c.cost_code,c.name,c.default_unit_id,c.description,c.sort_order,c.is_active,u.name_ar unit_name,u.symbol_ar unit_symbol FROM cost_codes c LEFT JOIN units u ON u.id=c.default_unit_id ORDER BY c.work_section_id,c.sort_order,c.cost_code'; $r=$this->db->execute($sql); return $r instanceof \mysqli_result?$r->fetch_all(MYSQLI_ASSOC):[]; }
    public function find(int $id): ?array { $r=$this->db->execute('SELECT id,work_section_id,cost_code,name,default_unit_id,description,sort_order,is_active FROM cost_codes WHERE id=? LIMIT 1',[$id]); $row=$r instanceof \mysqli_result?$r->fetch_assoc():null; return is_array($row)?$row:null; }
    public function codeExists(string $code,?int $exceptId=null): bool { $sql='SELECT 1 FROM cost_codes WHERE cost_code=?'.($exceptId===null?'':' AND id<>?').' LIMIT 1'; $params=$exceptId===null?[$code]:[$code,$exceptId]; $r=$this->db->execute($sql,$params); return $r instanceof \mysqli_result&&$r->num_rows>0; }
    public function create(CostCodeData $d): int { $this->db->execute('INSERT INTO cost_codes(work_section_id,cost_code,name,default_unit_id,description,sort_order) VALUES(?,?,?,?,?,?)',[$d->workSectionId,$d->costCode,$d->name,$d->defaultUnitId,$d->description,$d->sortOrder]); return (int)$this->db->connection()->insert_id; }
    public function update(int $id,CostCodeData $d): void { $this->db->execute('UPDATE cost_codes SET work_section_id=?,cost_code=?,name=?,default_unit_id=?,description=?,sort_order=? WHERE id=?',[$d->workSectionId,$d->costCode,$d->name,$d->defaultUnitId,$d->description,$d->sortOrder,$id]); }
    public function setActive(int $id,bool $active): void { $this->db->execute('UPDATE cost_codes SET is_active=? WHERE id=?',[$active?1:0,$id]); }
}
