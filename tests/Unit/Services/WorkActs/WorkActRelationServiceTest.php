<?php

namespace Tests\Unit\Services\WorkActs;

use App\Models\SaoPerformedWorkAct;
use App\Services\WorkActs\WorkActRelationService;
use Tests\TestCase;

class WorkActRelationServiceTest extends TestCase
{
    private WorkActRelationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WorkActRelationService;
    }

    public function test_parse_additional_agreement_numbers_splits_comma_list(): void
    {
        $this->assertSame(
            ['7', '8'],
            $this->service->parseAdditionalAgreementNumbers('7, 8')
        );
    }

    public function test_relates_by_main_contract_when_additional_list_empty(): void
    {
        $act = new SaoPerformedWorkAct([
            'contract_number' => 'Д-100',
            'customer_additional_number' => '7',
            'customer_inn' => '1234567890',
            'price' => 1500,
        ]);

        $this->assertTrue(
            $this->service->relatesToProject($act, 'Д-100', [], '1234567890')
        );
    }

    public function test_relates_by_main_contract_and_additional_number_from_list(): void
    {
        $act = new SaoPerformedWorkAct([
            'contract_number' => 'Д-100',
            'customer_additional_number' => '7',
            'customer_inn' => '1234567890',
            'price' => 1500,
        ]);

        $this->assertTrue(
            $this->service->relatesToProject($act, 'Д-100', ['7', '8'], '1234567890')
        );
        $this->assertFalse(
            $this->service->relatesToProject($act, 'Д-100', ['8', '9'], '1234567890')
        );
    }

    public function test_relates_by_inn_when_act_has_no_contracts(): void
    {
        $act = new SaoPerformedWorkAct([
            'contract_number' => '',
            'customer_additional_number' => '',
            'customer_inn' => '1234567890',
            'price' => 900,
        ]);

        $this->assertTrue(
            $this->service->relatesToProject($act, 'Д-100', [], '1234567890')
        );
    }

    public function test_sum_prefers_contract_match_over_inn_and_assigns_act_once(): void
    {
        $act = new SaoPerformedWorkAct([
            'contract_number' => 'Д-100',
            'customer_additional_number' => '7',
            'customer_inn' => '1234567890',
            'price' => 2500,
        ]);

        $contexts = collect([
            (object) [
                'projectId' => 1,
                'mainContractNumber' => 'Д-100',
                'additionalAgreementNumbers' => ['7'],
                'inn' => '1234567890',
            ],
            (object) [
                'projectId' => 2,
                'mainContractNumber' => 'Д-200',
                'additionalAgreementNumbers' => [],
                'inn' => '1234567890',
            ],
        ]);

        $sums = $this->service->sumActsByProject($contexts, collect([$act]));

        $this->assertSame(2500.0, $sums[1]);
        $this->assertNull($sums[2]);
    }

    public function test_without_main_contract_project_does_not_match(): void
    {
        $act = new SaoPerformedWorkAct([
            'contract_number' => 'Д-100',
            'customer_additional_number' => '',
            'customer_inn' => '1234567890',
            'price' => 100,
        ]);

        $this->assertFalse(
            $this->service->relatesToProject($act, '', [], '1234567890')
        );
    }
}
