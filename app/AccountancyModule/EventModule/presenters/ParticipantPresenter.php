<?php

declare(strict_types=1);

namespace App\AccountancyModule\EventModule;

use App\AccountancyModule\ParticipantTrait;
use Model\Auth\Resources\Event;
use Model\ExcelService;
use Model\ExportService;
use Model\MemberService;
use Model\Services\PdfRenderer;
use Nette\Application\AbortException;
use Nette\Application\BadRequestException;
use Skautis\Wsdl\WsdlException;
use function in_array;

class ParticipantPresenter extends BasePresenter
{
    use ParticipantTrait;

    /** @var bool */
    protected $isAllowParticipantDetail;

    /** @var bool */
    protected $isAllowParticipant;

    public function __construct(MemberService $member, ExportService $export, ExcelService $excel, PdfRenderer $pdf)
    {
        parent::__construct();
        $this->memberService = $member;
        $this->exportService = $export;
        $this->excelService  = $excel;
        $this->pdf           = $pdf;
    }

    protected function startup() : void
    {
        parent::startup();
        $this->traitStartup();
        $this->isAllowRepayment = false;
        $this->isAllowIsAccount = false;

        $isDraft      = $this->event->ID_EventGeneralState === 'draft';
        $authorizator = $this->authorizator;

        $this->isAllowParticipantDetail = $authorizator->isAllowed(Event::ACCESS_DETAIL, $this->aid);
        $this->isAllowParticipantDelete = $isDraft && $authorizator->isAllowed(Event::REMOVE_PARTICIPANT, $this->aid);
        $this->isAllowParticipantInsert = $isDraft && $authorizator->isAllowed(Event::UPDATE_PARTICIPANT, $this->aid);
        $this->isAllowParticipantUpdate = $this->isAllowParticipantInsert;

        $this->template->setParameters(
            [
            'isAllowParticipantDetail' => $this->isAllowParticipantDetail,
            'isAllowParticipantDelete' => $this->isAllowParticipantDelete,
            'isAllowParticipantInsert' => $this->isAllowParticipantInsert,
            'isAllowParticipantUpdate' => $this->isAllowParticipantUpdate,
            'isAllowParticipantUpdateLocal' => $this->isAllowParticipantUpdate,
            'isAllowRepayment' => $this->isAllowRepayment,
            'isAllowIsAccount' => $this->isAllowIsAccount,
            ]
        );
    }

    /**
     *
     * @param bool $dp - disabled person
     * @throws WsdlException
     */
    public function renderDefault(
        ?int $aid,
        ?int $uid = null,
        bool $dp = false,
        ?string $sort = null,
        bool $regNums = false
    ) : void {
        if (! $this->authorizator->isAllowed(Event::ACCESS_PARTICIPANTS, $this->aid)) {
            $this->flashMessage('Nemáte právo prohlížeč účastníky akce', 'danger');
            $this->redirect('Event:');
        }

        $this->traitDefault($dp, $sort, $regNums);

        if (! $this->isAjax()) {
            return;
        }

        $this->redrawControl('contentSnip');
    }

    /**
     * @param int|float|string $value
     * @throws AbortException
     * @throws BadRequestException
     */
    public function actionEditField(?int $aid = null, ?int $id = null, ?string $field = null, $value = null) : void
    {
        if ($aid === null || $id === null || $field === null || $value === null) {
            throw new BadRequestException();
        }

        if (! $this->isAllowParticipantUpdate) {
            $this->flashMessage('Nemáte oprávnění měnit účastníkův jejich údaje.', 'danger');
            if ($this->isAjax()) {
                $this->sendPayload();
            } else {
                $this->redirect('Default:');
            }
        }

        if (! in_array($field, ['days', 'payment'])) {
            $this->payload->message = 'Error';
            $this->sendPayload();
        }
        $this->eventService->getParticipants()->update($id, $aid, [$field => $value]);
        $this->payload->message = 'Success';
        $this->sendPayload();
    }
}
