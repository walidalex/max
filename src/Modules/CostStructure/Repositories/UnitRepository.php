<?php
declare(strict_types=1);
namespace App\Modules\CostStructure\Repositories;
use App\Core\Database\Database;
use App\Modules\CostStructure\DTOs\UnitData;
final class UnitRepository
{
    public function __construct(private readonly Database $db) {}
    public function all(): array { $r=$this->db->execute('SELECT id,code,name_ar,symbol_ar,sort_order,is_active FROM units ORDER BY sort_order,name_ar'); return $r instanceof \mysqli_result?$r->fetch_all(MYSQLI_ASSOC):[]; }
    public function find(int $id): ?array { $r=$this->db->execute('SELECT id,code,name_ar,symbol_ar,sort_order,is_active FROM units WHERE id=? LIMIT 1',[$id]); $row=$r instanceof \mysqli_result?$r->fetch_assoc():null; return is_array($row)?$row:null; }
    public function codeExists(string $code,?int $exceptId=null): bool { $sql='SELECT 1 FROM units WHERE code=?'.($exceptId===null?'':' AND id<>?').' LIMIT 1'; $params=$exceptId===null?[$code]:[$code,$exceptId]; $r=$this->db->execute($sql,$params); return $r instanceof \mysqli_result&&$r->num_rows>0; }
    public function create(UnitData $d): int { $this->db->execute('INSERT INTO units(code,name_ar,symbol_ar,sort_order) VALUES(?,?,?,?)',[$d->code,$d->nameAr,$d->symbolAr,$d->sortOrder]); return (int)$this->db->connection()->insert_id; }
    public function update(int $id,UnitData $d): void { $this->db->execute('UPDATE units SET code=?,name_ar=?,symbol_ar=?,sort_order=? WHERE id=?',[$d->code,$d->nameAr,$d->symbolAr,$d->sortOrder,$id]); }
    public function setActive(int $id,bool $active): void { $this->db->execute('UPDATE units SET is_active=? WHERE id=?',[$active?1:0,$id]); }
}
