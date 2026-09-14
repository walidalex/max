<?php
declare(strict_types=1);
namespace App\Modules\CompanyProfile\Repositories;
use App\Core\Database\Database;
use App\Modules\CompanyProfile\DTOs\CompanyProfileData;
final class CompanyProfileRepository
{
    public function __construct(private readonly Database $db) {}
    /** @return array<string,mixed>|null */
    public function get():?array
    {
        $result=$this->db->execute('SELECT id,name,legal_name,tax_number,commercial_registration,phone,mobile,email,website,address,logo_path,created_at,updated_at FROM company_profile WHERE id=1 LIMIT 1');
        $row=$result instanceof \mysqli_result?$result->fetch_assoc():null;return is_array($row)?$row:null;
    }
    public function update(CompanyProfileData $data,?string $logoPath):void
    {
        $sql='UPDATE company_profile SET name=?,legal_name=?,tax_number=?,commercial_registration=?,phone=?,mobile=?,email=?,website=?,address=?,logo_path=? WHERE id=1';
        $this->db->execute($sql,[$data->name,$data->legalName,$data->taxNumber,$data->commercialRegistration,$data->phone,$data->mobile,$data->email,$data->website,$data->address,$logoPath]);
    }
}
