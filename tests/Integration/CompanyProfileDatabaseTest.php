<?php
declare(strict_types=1);
use App\Modules\CompanyProfile\Repositories\CompanyProfileRepository;

/** @var CompanyProfileRepository $companyRepository */
$companyRepository=$app->make(CompanyProfileRepository::class);
$company=$companyRepository->get();
if($company===null||(int)$company['id']!==1){throw new RuntimeException('Singleton company profile record is missing.');}
