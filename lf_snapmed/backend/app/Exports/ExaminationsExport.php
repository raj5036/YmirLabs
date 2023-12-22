<?php

namespace App\Exports;

use App\Examination;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ExaminationsExport implements FromCollection, WithHeadings
{
    protected $from, $to, $partner;

    function __construct($from, $to, $partner)
    {
        $this->from = $from;
        $this->to = $to;
        $this->partner = $partner;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $partner = $this->partner;
        $examinations = Examination::where('complete', '=', true)
            ->where(function ($query) {
                $query->where('diagnosed', '=', true)->orWhere('category', '=', 'video');
            })
            ->whereBetween('created_at', [$this->from, $this->to])
            ->whereHas('client', function ($query) use ($partner) {
                $query->where('partner', '=', $partner);
            })
            ->orderBy('payment_type', 'desc')
            ->orderBy('category', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $examinationArr = array();

        foreach ($examinations as $examination) {
            $description_private = '';
            $prescription = '';
            $description_private_count = 0;
            if ($examination->diagnoses) {
                foreach ($examination->diagnoses as $diagnosis) {
                    $description_private_count++;
                    if ($diagnosis->description_private) {
                        $description_private .= '#' . $description_private_count . ': ' . $diagnosis->description_private . ' ';
                    }
                    if ($diagnosis->icd_codes) {
                        $icds = array_map(function ($object) { return $object['name'] . ' (' . $object['code'] . ')'; }, $diagnosis->icd_codes);
                        $description_private .= '#' . $description_private_count . ': ' . implode(', ', $icds) . ' ';
                    }
                    $prescription .= '#' . $description_private_count . ': ' . ($diagnosis->is_prescribed ? 'Yes' : 'No') . ' ';
                }
            }

            $examObj = new \stdClass();
            $examObj->uuid = $examination->uuid;
            $examObj->createdAt = Carbon::parse($examination->created_at)->format('d/m/Y H:i');

            if ($partner === 'Storebrand') {
                $examObj->name = $examination->client->firstname . ' ' . $examination->client->lastname;
                $partnerClaimNumber = '';
                if ($examination->partnerClaim) {
                    $partnerClaimNumber = $examination->partnerClaim->claimnumber;
                }
                $examObj->policyNo = $partnerClaimNumber;
                $examObj->ssn = Crypt::decryptString($examination->client->ssn);
                $examObj->brand = ($examination->client->brand !== null ? $examination->client->brand : 'stb');
            }
            $examObj->descPrivate = $description_private;
            $examObj->category = ($examination->category === 'video' ? 'Video-konsultasjon' : 'Bilde-konsultasjon');
            $examObj->isPaid = ($examination->payment_type === Examination::PAYMENT_TYPE_CARD ? 'Paid' : 'Unpaid');
            $examObj->isPrescribed = $prescription;

            array_push($examinationArr, $examObj);
        }
        return collect($examinationArr);
    }

    /**
     * Generate header for excel
     */
    public function headings(): array
    {
        switch ($this->partner) {
            case 'Storebrand':
                return [
                    'Id',
                    'Created At',
                    'Name',
                    'Policy No',
                    'SSN',
                    'Brand',
                    'Private Description',
                    'Type',
                    'Is Paid',
                    'Is Prescribed'
                ];
                break;
            case 'Oslo':
                return [
                    'Id',
                    'Created At',
                    'Private Description',
                    'Type',
                    'Is Paid',
                    'Is Prescribed'
                ];
                break;
        }
    }
}
