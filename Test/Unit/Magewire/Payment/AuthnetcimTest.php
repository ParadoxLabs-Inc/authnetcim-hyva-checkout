<?php declare(strict_types=1);
/**
 * Copyright © 2026-present ParadoxLabs, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * Need help? Try our knowledgebase and support system:
 *
 * @link https://support.paradoxlabs.com
 */

namespace ParadoxLabs\AuthnetcimHyvaCheckout\Test\Unit\Magewire\Payment;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Payment\Model\MethodInterface;
use ParadoxLabs\Authnetcim\Model\ConfigProvider;
use ParadoxLabs\Authnetcim\Model\Service\AcceptCustomer\FrontendRequest as AcceptCustomerService;
use ParadoxLabs\Authnetcim\Model\Service\AcceptHosted\FrontendRequest as AcceptHostedService;
use ParadoxLabs\AuthnetcimHyvaCheckout\Magewire\Payment\Authnetcim;
use ParadoxLabs\AuthnetcimHyvaCheckout\ViewModel\PaymentForm;
use ParadoxLabs\TokenBase\Api\CardRepositoryInterface;
use ParadoxLabs\TokenBase\Helper\Data;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Rakit\Validation\Validator;

class AuthnetcimTest extends TestCase
{
    private AcceptHostedService&MockObject $acceptHostedService;

    private AcceptCustomerService&MockObject $acceptCustomerService;

    private PaymentForm&MockObject $paymentForm;

    protected function setUp(): void
    {
        $this->acceptHostedService = $this->createMock(AcceptHostedService::class);
        $this->acceptCustomerService = $this->createMock(AcceptCustomerService::class);
        $this->paymentForm = $this->createMock(PaymentForm::class);
    }

    public function testInitHostedFormSkipsTokenRequestForAcceptJs(): void
    {
        $component = $this->createComponent(ConfigProvider::FORM_ACCEPTJS, 'authorize');

        $this->acceptHostedService->expects($this->never())->method('getParams');
        $this->acceptCustomerService->expects($this->never())->method('getParams');
        $component->expects($this->never())->method('dispatchBrowserEvent');

        $component->initHostedForm();
    }

    public function testInitHostedFormRequestsTokenForHostedForm(): void
    {
        $component = $this->createComponent(ConfigProvider::FORM_HOSTED, 'authorize');

        $this->acceptHostedService->expects($this->once())
            ->method('getParams')
            ->willReturn(['token' => 'abc']);
        $this->acceptCustomerService->expects($this->never())->method('getParams');
        $component->expects($this->once())
            ->method('dispatchBrowserEvent')
            ->with('authnetcimInitHostedForm', ['token' => 'abc']);

        $component->initHostedForm();
    }

    public function testInitHostedFormTreatsUnsetFormTypeAsHosted(): void
    {
        $component = $this->createComponent(null, 'order');

        $this->acceptHostedService->expects($this->never())->method('getParams');
        $this->acceptCustomerService->expects($this->once())
            ->method('getParams')
            ->willReturn(['token' => 'def']);
        $component->expects($this->once())->method('dispatchBrowserEvent');

        $component->initHostedForm();
    }

    private function createComponent(?string $formType, string $paymentAction): Authnetcim&MockObject
    {
        $method = $this->createMock(MethodInterface::class);
        $method->method('getConfigData')->willReturnCallback(
            static fn (string $field) => match ($field) {
                'form_type' => $formType,
                'payment_action' => $paymentAction,
                default => null,
            }
        );
        $this->paymentForm->method('getMethod')->with('authnetcim')->willReturn($method);

        return $this->getMockBuilder(Authnetcim::class)
            ->setConstructorArgs(
                [
                    $this->createMock(Validator::class),
                    $this->createMock(CheckoutSession::class),
                    $this->acceptCustomerService,
                    $this->acceptHostedService,
                    $this->createMock(CardRepositoryInterface::class),
                    $this->createMock(Data::class),
                    $this->paymentForm,
                ]
            )
            ->onlyMethods(['dispatchBrowserEvent'])
            ->getMock();
    }
}
