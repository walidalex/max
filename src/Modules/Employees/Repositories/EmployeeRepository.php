<?php
declare(strict_types=1);
namespace App\Modules\Employees\Repositories;
use App\Core\Database\Database;
use App\Modules\Employees\DTOs\EmployeeData;
final class EmployeeRepository{public function __construct(private readonly Database$db){}public function all():array{$r=$this->db->execute('SELECT * FROM employees ORDER BY employee_code');return$r instanceof \mysqli_result?$r->fetch_all(MYSQLI_ASSOC):[];}public function find(int$id):?array{$r=$this->db->execute('SELECT * FROM employees WHERE id=?',[$id]);$x=$r instanceof \mysqli_result?$r->fetch_assoc():null;return is_array($x)?$x:null;}public function save(string$code,EmployeeData$d,?int$id):int{if($id===null){$this->db->execute('INSERT INTO employees(employee_code,name,phone,email,is_active) VALUES(?,?,?,?,?)',[$code,$d->name,$d->phone,$d->email,$d->isActive]);return(int)$this->db->connection()->insert_id;}$this->db->execute('UPDATE employees SET name=?,phone=?,email=?,is_active=? WHERE id=?',[$d->name,$d->phone,$d->email,$d->isActive,$id]);return$id;}}
