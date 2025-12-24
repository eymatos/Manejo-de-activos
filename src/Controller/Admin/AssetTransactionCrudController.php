<?php

namespace App\Controller\Admin;

use App\Entity\AssetTransaction;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;
use Dompdf\Dompdf;
use Dompdf\Options;

class AssetTransactionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AssetTransaction::class;
    }

    /**
     * LÓGICA DE FILTRADO AUTOMÁTICO POR ÁREA
     */
    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        /** @var User $user */
        $user = $this->getUser();
        if (!$user || $this->isGranted('ROLE_ADMIN')) return $qb;

        $rootAlias = $qb->getRootAliases()[0];

        if ($this->isGranted('ROLE_CONTABILIDAD')) {
            $qb->andWhere(sprintf('%s.accountingApprovedBy IS NULL', $rootAlias));
        } elseif ($this->isGranted('ROLE_TECNOLOGIA')) {
            $qb->join(sprintf('%s.asset', $rootAlias), 'a')
               ->andWhere('a.category = :cat')
               ->andWhere(sprintf('%s.techApprovedBy IS NULL', $rootAlias))
               ->setParameter('cat', 'COMPUTO');
        } else {
            $userDept = $user->getDepartment();
            if ($userDept) {
                $qb->andWhere(sprintf('%s.destinationDepartment = :myDept', $rootAlias))
                   ->andWhere(sprintf('%s.receivedBy IS NULL', $rootAlias))
                   ->setParameter('myDept', $userDept);
            } else {
                $qb->andWhere('1 = 0');
            }
        }
        return $qb;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Transacción')
            ->setEntityLabelInPlural('Historial de Movimientos')
            ->setPageTitle('index', 'Gestión de Traslados y Aprobaciones')
            ->setDefaultSort(['createdAt' => 'DESC'])
            // AJUSTE: Aseguramos que el CSRF esté activo y bien configurado
            ->setFormOptions([
                'csrf_protection' => true,
            ]);
    }

    public function configureActions(Actions $actions): Actions
    {
        $descargarActa = Action::new('descargarActa', 'Descargar Acta', 'fa fa-file-pdf')
            ->linkToCrudAction('descargarActa')
            ->displayIf(static function (AssetTransaction $entity) {
                return $entity->getStatus() === 'ACEPTADO';
            })
            ->setCssClass('btn btn-outline-primary');

        return $actions
            ->add(Crud::PAGE_INDEX, $descargarActa)
            ->add(Crud::PAGE_DETAIL, $descargarActa)
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action->setLabel('Gestionar Firmas')->setIcon('fa fa-pen-fancy');
            })
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action->displayIf(static function (AssetTransaction $entity) {
                    return $entity->getStatus() === 'SOLICITADO';
                });
            });
    }

    public function descargarActa(AdminContext $context): Response
    {
        /** @var AssetTransaction $transaction */
        $transaction = $context->getEntity()->getInstance();

        $html = $this->renderView('admin/pdf_acta_entrega.html.twig', [
            't' => $transaction,
            'fecha' => new \DateTime(),
        ]);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Acta_Entrega_'.$transaction->getId().'.pdf"',
        ]);
    }

    public function configureFields(string $pageName): iterable
    {
        $isEdit = $pageName === Crud::PAGE_EDIT;
        $isNew = $pageName === Crud::PAGE_NEW;
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $entity = $this->getContext()?->getEntity()->getInstance();

        yield FormField::addPanel('Información del Traslado')->setIcon('fa fa-file-contract');
        yield DateTimeField::new('createdAt', 'Fecha')->setFormat('dd/MM/yy HH:mm')->onlyOnIndex();

        yield AssociationField::new('asset', 'Activo a Trasladar')
            ->setFormTypeOption('disabled', $isEdit)
            ->setQueryBuilder(function (QueryBuilder $qb) use ($currentUser) {
                if ($this->isGranted('ROLE_ADMIN')) return $qb;
                return $qb->andWhere('entity.currentDepartment = :dept')
                          ->setParameter('dept', $currentUser->getDepartment());
            });

        yield ChoiceField::new('type', 'Tipo de Operación')
            ->setChoices([
                'Traslado entre Áreas' => 'TRASLADO',
                'Descargo de Activo' => 'DESCARGO',
                'Reparación Externa' => 'REPARACION'
            ])
            ->renderAsBadges()
            ->setFormTypeOption('disabled', $isEdit);

        yield ChoiceField::new('status', 'Estado del Expediente')
            ->setChoices([
                'Pendiente de Firmas' => 'SOLICITADO',
                'Completado y Ejecutado' => 'ACEPTADO',
                'Rechazado' => 'RECHAZADO',
            ])
            ->renderAsBadges([
                'SOLICITADO' => 'warning', 'ACEPTADO' => 'success', 'RECHAZADO' => 'danger',
            ])
            ->setFormTypeOption('disabled', true);

        yield FormField::addPanel('Participantes y Responsables')->setIcon('fa fa-users');
        yield AssociationField::new('originDepartment', 'Área de Origen (Entrega)')->onlyOnIndex();
        yield AssociationField::new('destinationDepartment', 'Área de Destino (Recibe)')
            ->setFormTypeOption('disabled', $isEdit);

        yield TextField::new('assignedTo', 'Funcionario Responsable (Nombre)')
            ->setFormTypeOption('disabled', $isEdit);

        yield FormField::addPanel('Firmas y Autorizaciones Digitales')->setIcon('fa fa-signature');

        $canSignTech = $this->isGranted('ROLE_TECNOLOGIA') || $this->isGranted('ROLE_ADMIN');
        $canSignAccounting = $this->isGranted('ROLE_CONTABILIDAD') || $this->isGranted('ROLE_ADMIN');
        $isRecipientDept = $entity instanceof AssetTransaction &&
                           $currentUser && $currentUser->getDepartment() === $entity->getDestinationDepartment();
        $canSignReceiver = $isRecipientDept || $this->isGranted('ROLE_ADMIN');

        yield AssociationField::new('techApprovedBy', 'Visto Bueno Tecnología')
            ->setFormTypeOption('disabled', !$canSignTech)
            ->onlyOnForms();

        yield AssociationField::new('accountingApprovedBy', 'Autorización Contabilidad')
            ->setFormTypeOption('disabled', !$canSignAccounting)
            ->onlyOnForms();

        yield AssociationField::new('receivedBy', 'Recibido Conforme por (Destino)')
            ->setFormTypeOption('disabled', !$canSignReceiver)
            ->onlyOnForms();

        yield DateTimeField::new('techApprovedAt', 'Fecha Firma IT')->onlyOnDetail();
        yield DateTimeField::new('accountingApprovedAt', 'Fecha Firma Contab.')->onlyOnDetail();
        yield DateTimeField::new('receivedAt', 'Fecha Recepción')->onlyOnDetail();

        yield FormField::addPanel('Observaciones Adicionales')->setIcon('fa fa-comment');
        yield TextField::new('observations', 'Notas del proceso');
    }
}
