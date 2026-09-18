<?php
declare(strict_types=1);
namespace App\Modules\Employees\Services;
use App\Core\Exceptions\BusinessRuleException;
use App\Modules\Employees\DTOs\EmployeeData;
use App\Modules\Employees\Repositories\EmployeeRepository;
use App\Shared\Numbering\NumberGeneratorService;
final class EmployeeService{public function __construct(private readonly EmployeeRepository$repo,private readonly NumberGeneratorService$numbers){}public function all():array{return$this->repo->all();}public function find(int$id):array{return$this->repo->find($id)??throw new BusinessRuleException('الموظف غير موجود.');}public function save(EmployeeData$d,?int$id=null):int{if($id!==null)$this->find($id);return$this->repo->save($id===null?$this->numbers->nextEmployeeCode():'',$d,$id);}}
