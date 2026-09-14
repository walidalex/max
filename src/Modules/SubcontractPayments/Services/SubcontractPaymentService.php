<?php

declare(strict_types=1);

namespace App\Modules\SubcontractPayments\Services;

use App\Core\Auth\Auth;
use App\Core\Database\Database;
use App\Core\Exceptions\BusinessRuleException;
use App\Core\Support\Logger;
use App\Modules\SubcontractCertificates\Services\SubcontractCertificateService;
use App\Modules\SubcontractPayments\DTOs\SubcontractPaymentData;
use App\Modules\SubcontractPayments\DTOs\SubcontractPaymentTableQuery;
use App\Modules\SubcontractPayments\Repositories\SubcontractPaymentRepository;
use App\Shared\Numbering\NumberGeneratorService;

final class SubcontractPaymentService
{
    private const ELIGIBLE = ['active','suspended','completed'];
    public function __construct(private readonly SubcontractPaymentRepository $payments,private readonly SubcontractCertificateService $certificates,private readonly NumberGeneratorService $numbers,private readonly Database $db,private readonly Auth $auth,private readonly Logger $logger){}
    public function subcontract(int$id):array{return$this->payments->subcontract($id)??throw new BusinessRuleException('عقد مقاول الباطن غير موجود.');}
    public function find(int$id):array{return$this->payments->find($id)??throw new BusinessRuleException('دفعة مقاول الباطن غير موجودة.');}
    public function save(SubcontractPaymentData$d,?int$id=null):int{return$this->db->transaction(function()use($d,$id){$s=$this->payments->subcontract($d->subcontractId,true)??throw new BusinessRuleException('عقد مقاول الباطن غير موجود.');$old=$id===null?null:$this->payments->find($id,true);if(!in_array($s['status'],self::ELIGIBLE,true))throw new BusinessRuleException('لا يمكن إنشاء أو تعديل دفعة لعقد في حالته الحالية.');if($old!==null&&((int)$old['subcontract_id']!==$d->subcontractId||$old['status']!=='draft'))throw new BusinessRuleException('الدفعة غير قابلة للتعديل.');if($old!==null&&preg_match('/^SPAY-(\d{4})-/',(string)$old['payment_code'],$m)&&$m[1]!==substr($d->paymentDate,0,4))throw new BusinessRuleException('لا يمكن تغيير سنة تاريخ الدفع. ألغِ المسودة وأنشئ دفعة جديدة للسنة المطلوبة.');if($id===null)return$this->payments->create($this->numbers->nextSubcontractPaymentCode((int)substr($d->paymentDate,0,4)),$d,$s,$this->user());$this->payments->update($id,$d);return$id;});}
    public function post(int$id):void{$this->db->transaction(function()use($id){$probe=$this->find($id);$s=$this->payments->subcontract((int)$probe['subcontract_id'],true)??throw new BusinessRuleException('عقد مقاول الباطن غير موجود.');$p=$this->payments->find($id,true)??throw new BusinessRuleException('الدفعة غير موجودة.');if($p['status']!=='draft')throw new BusinessRuleException('يمكن ترحيل مسودة الدفعة فقط.');if(!in_array($s['status'],self::ELIGIBLE,true))throw new BusinessRuleException('حالة العقد لا تسمح بترحيل الدفعة.');$latest=$this->payments->latestPosted((int)$s['id']);if($latest!==null&&$p['payment_date']<$latest['payment_date'])throw new BusinessRuleException('لا يمكن ترحيل دفعة بتاريخ أقدم من آخر دفعة مرحلة.');$earned=$this->certificates->getApprovedEarnedValue((int)$s['id']);$progress=$this->certificates->getApprovedProgressPercentage((int)$s['id']);$posted=$this->payments->postedTotal((int)$s['id']);$available=$this->payments->availability($earned,$posted);if((int)$available['inconsistent']===1){$this->logger->error('Subcontract payment entitlement inconsistency',['subcontract_id'=>(int)$s['id'],'approved_earned_value'=>$earned,'posted_payments'=>$posted]);throw new BusinessRuleException('يوجد عدم اتساق مالي في سجل العقد. لا يمكن ترحيل دفعة جديدة.');}if($this->payments->amountExceeds((string)$p['amount'],(string)$available['available']))throw new BusinessRuleException('مبلغ الدفعة يتجاوز المتاح للدفع.');if($this->payments->amountExceeds('0.01',$earned))throw new BusinessRuleException('لا توجد قيمة أعمال معتمدة متاحة للدفع.');$this->payments->post($id,$this->user(),['approved_progress_percentage'=>$progress,'approved_earned_value'=>$earned,'posted_payments_total'=>$posted,'available_payment_amount'=>(string)$available['available']]);});}
    public function cancel(int$id):void{$this->db->transaction(function()use($id){$probe=$this->find($id);$this->payments->subcontract((int)$probe['subcontract_id'],true);$p=$this->payments->find($id,true)??throw new BusinessRuleException('الدفعة غير موجودة.');if($p['status']!=='draft')throw new BusinessRuleException('يمكن إلغاء مسودة الدفعة فقط.');$this->payments->cancel($id,$this->user());});}
    public function getPostedPaymentsTotal(int$id):string{$this->subcontract($id);return$this->payments->postedTotal($id);}
    public function getAvailablePaymentAmount(int$id):string{return(string)$this->financialSummary($id)['available_payment_amount'];}
    public function financialSummary(int$id):array{$s=$this->subcontract($id);$earned=$this->certificates->getApprovedEarnedValue($id);$progress=$this->certificates->getApprovedProgressPercentage($id);$posted=$this->payments->postedTotal($id);$a=$this->payments->availability($earned,$posted);if((int)$a['inconsistent']===1)$this->logger->error('Subcontract payment entitlement inconsistency',['subcontract_id'=>$id,'approved_earned_value'=>$earned,'posted_payments'=>$posted]);return['contract_value'=>(string)($s['contract_value']??'0.00'),'approved_progress_percentage'=>$progress,'approved_earned_value'=>$earned,'posted_payments_total'=>$posted,'available_payment_amount'=>(string)$a['available'],'inconsistent'=>(bool)$a['inconsistent']];}
    public function dataTable(SubcontractPaymentTableQuery$q):array{$this->subcontract($q->subcontractId);$r=$this->payments->dataTable($q);return['draw'=>$q->draw,'recordsTotal'=>$r['recordsTotal'],'recordsFiltered'=>$r['recordsFiltered'],'data'=>$r['rows']];}
    private function user():int{return$this->auth->id()??throw new BusinessRuleException('تعذر تحديد المستخدم الحالي.');}
}
