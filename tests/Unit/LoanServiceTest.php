<?php

namespace Tests\Unit;

use App\Models\Loan;
use App\Models\ScheduledRepayment;
use App\Models\User;
use App\Services\LoanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class LoanServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected LoanService $loanService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->loanService = new LoanService();
    }

    public function testServiceCanCreateLoanOfForACustomer()
    {
        $terms = 6;
        $amount = rand(1000000, 99999999);
        $outstandingAmount = $amount;
        $currencyCode = Loan::CURRENCY_IDR;
        $processedAt = Carbon::now()->format('Y-m-d H:i:s');

        $loan = $this->loanService->createLoan($this->user, $amount, $currencyCode, $terms, $processedAt, $outstandingAmount);

        // Asserting Loan values
        $this->assertDatabaseHas('loans', [
            'id'                    => $loan->id,
            'user_id'               => $this->user->id,
            'amount'                => $amount,
            'terms'                 => $terms,
            'outstanding_amount'    => $outstandingAmount,
            'currency_code'         => $currencyCode,
            'processed_at'          => Carbon::now()->format('Y-m-d H:i:s'),
            'status'                => Loan::STATUS_DUE,
        ]);

        // Asserting Scheduled Repayments
        $this->assertCount($terms, $loan->scheduledRepayments);
        $dataArr = [];
        for ($i=0; $i < $terms ; $i++) {
            array_push($dataArr,
            [
                'loan_id' => $loan->id,
                'amount' => 0000000,
                'currency_code' => $currencyCode,
                'due_date' => Carbon::now()->format('Y-m-d H:i:s'),
                'status' => ScheduledRepayment::STATUS_DUE,
            ]);
        }


        return $dataArr;
    }

    public function testServiceCanRepayAScheduledRepayment()
    {
        $terms = 3;
        $amount = rand(1000000, 99999999);
        $outstandingAmount = $amount;
        $loan = Loan::factory()->create([
            'user_id' => $this->user->id,
            'terms' => $terms,
            'amount' => $amount,
            'outstanding_amount' => $outstandingAmount,
            'currency_code' => Loan::CURRENCY_IDR,
            'processed_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);

        for ($i=0; $i < $terms ; $i++) {
            $scheduledRepaymentOne =  ScheduledRepayment::factory()->create([
                'loan_id' => $loan->id,
                'amount' => $amount / $terms,
                'currency_code' => Loan::CURRENCY_IDR,
                'due_date' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);
        }

        $receivedRepayment = $amount / $terms;
        $currencyCode = Loan::CURRENCY_IDR;
        $receivedAt = Carbon::now()->format('Y-m-d H:i:s');

        $loan = $this->loanService->repayLoan($loan, $receivedRepayment, $currencyCode, $receivedAt);

        // Asserting Loan values
        $dataArr = [
            'id'                    => $loan->id,
            'user_id'               => $this->user->id,
            'amount'                => $loan->amount,
            'terms'                 => $loan->terms,
            'outstanding_amount'    => $loan->outstanding_amount,
            'currency_code'         => $loan->currency_code,
            'processed_at'          => Carbon::now()->format('Y-m-d H:i:s'),
            'status'                => Loan::STATUS_DUE,
            'created_at'            => Carbon::now()->format('Y-m-d H:i:s'),
            'updated_at'            => Carbon::now()->format('Y-m-d H:i:s'),
            'deleted_at'            => null
        ];

        $this->assertDatabaseHas('loans', $dataArr);
        return $dataArr;

    }

    public function testServiceCanRepayAScheduledRepaymentConsecutively()
    {
        $terms = 6;
        $amount = rand(1000000, 99999999);
        $outstandingAmount = $amount;

        $loan = Loan::factory()->create([
            'user_id'               => $this->user->id,
            'terms'                 => $terms,
            'amount'                => $amount,
            'outstanding_amount'    => $outstandingAmount,
            'currency_code'         => Loan::CURRENCY_IDR,
            'processed_at'          => Carbon::now()->format('Y-m-d H:i:s'),
        ]);

        // First two scheduled repayments are already repaid
        $scheduledRepaymentOne =  ScheduledRepayment::factory()->create([
            'loan_id' => $loan->id,
            'amount' => $amount / $terms,
            'currency_code' => Loan::CURRENCY_IDR,
            'due_date' => Carbon::now()->format('Y-m-d H:i:s'),
            'status' => ScheduledRepayment::STATUS_REPAID,
        ]);
        $scheduledRepaymentTwo =  ScheduledRepayment::factory()->create([
            'loan_id' => $loan->id,
            'amount' => $amount / $terms,
            'currency_code' => Loan::CURRENCY_IDR,
            'due_date' => Carbon::now()->format('Y-m-d H:i:s'),
            'status' => ScheduledRepayment::STATUS_REPAID,
        ]);
        // Only the last one is due
        $scheduledRepaymentThree =  ScheduledRepayment::factory()->create([
            'loan_id' => $loan->id,
            'amount' => $amount / $terms,
            'currency_code' => Loan::CURRENCY_IDR,
            'due_date' => Carbon::now()->format('Y-m-d H:i:s'),
            'status' => ScheduledRepayment::STATUS_DUE,
        ]);

        $receivedRepayment = $amount / $terms;
        $currencyCode = Loan::CURRENCY_IDR;
        $receivedAt = Carbon::now()->format('Y-m-d H:i:s');

        // Repaying the last one
        $loan = $this->loanService->repayLoan($loan, $receivedRepayment, $currencyCode, $receivedAt);

        $dataArr = [
            'id'                    => $loan->id,
            'user_id'               => $this->user->id,
            'amount'                => $amount,
            'terms'                 => $terms,
            'outstanding_amount'    => $outstandingAmount,
            'currency_code'         => $currencyCode,
            'processed_at'          => Carbon::now()->format('Y-m-d H:i:s'),
            'status'                => Loan::STATUS_REPAID,
            'created_at'            => Carbon::now()->format('Y-m-d H:i:s'),
            'updated_at'            => Carbon::now()->format('Y-m-d H:i:s'),
            'deleted_at'            => null
        ];
        return $dataArr;
    }

    public function testServiceCanRepayMultipleScheduledRepayments()
    {
        $terms = 6;
        $amount = rand(1000000, 99999999);
        $outstandingAmount = $amount;
        $lastPayment = 0;

        $loan = Loan::factory()->create([
            'user_id'       => $this->user->id,
            'terms'         => $terms,
            'amount'        => $amount,
            'currency_code' => Loan::CURRENCY_IDR,
            'processed_at'  => Carbon::now()->format('Y-m-d H:i:s'),
        ]);

        $scheduledRepaymentOne =  ScheduledRepayment::factory()->create([
            'loan_id'       => $loan->id,
            'amount'        => $amount / $terms,
            'currency_code' => Loan::CURRENCY_IDR,
            'due_date'      => Carbon::now()->format('Y-m-d H:i:s'),
            'status'        => ScheduledRepayment::STATUS_DUE,
        ]);
        $scheduledRepaymentTwo =  ScheduledRepayment::factory()->create([
            'loan_id'       => $loan->id,
            'amount'        => $amount / $terms,
            'currency_code' => Loan::CURRENCY_IDR,
            'due_date'      => Carbon::now()->format('Y-m-d H:i:s'),
            'status'        => ScheduledRepayment::STATUS_DUE,
        ]);
        $scheduledRepaymentThree =  ScheduledRepayment::factory()->create([
            'loan_id'       => $loan->id,
            'amount'        => $amount / $terms,
            'currency_code' => Loan::CURRENCY_IDR,
            'due_date'      => Carbon::now()->format('Y-m-d H:i:s'),
            'status'        => ScheduledRepayment::STATUS_DUE,
        ]);

        // Paying more than the first scheduled repayment amount
        $receivedRepayment = $amount / $terms;
        $currencyCode = Loan::CURRENCY_IDR;
        $receivedAt = Carbon::now()->format('Y-m-d H:i:s');

        // Repaying
        $loan = $this->loanService->repayLoan($loan, $receivedRepayment, $currencyCode, $receivedAt);

        // Asserting Loan values
        $dataArr = [
            'id'                    => $loan->id,
            'user_id'               => $this->user->id,
            'amount'                => $amount,
            'term'                  => $terms,
            'outstanding_amount'    => $outstandingAmount,
            'currency_code'         => $currencyCode,
            'processed_at'          => Carbon::now()->format('Y-m-d H:i:s'),
            'status'                => Loan::STATUS_DUE,
            'created_at'            => Carbon::now()->format('Y-m-d H:i:s'),
            'updated_at'            => Carbon::now()->format('Y-m-d H:i:s'),
            'deleted_at'            => null
        ];
        return $dataArr;

    }
}
