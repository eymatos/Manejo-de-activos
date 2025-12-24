<?php

namespace App\Controller\Admin;

use App\Entity\Asset;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use Doctrine\ORM\QueryBuilder;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Response;
use Dompdf\Dompdf;
use Dompdf\Options;

class AssetCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Asset::class;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        /** @var User $user */
        $user = $this->getUser();
        if (!$user) return $qb;

        if ($this->isGranted('ROLE_ADMIN')) {
            return $qb;
        }

        $userDept = $user->getDepartment();
        $rootAlias = $qb->getRootAliases()[0];

        if ($userDept) {
            $qb->andWhere(sprintf('%s.currentDepartment = :myDept', $rootAlias))
               ->setParameter('myDept', $userDept);
        } else {
            $qb->andWhere('1 = 0');
        }

        return $qb;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Activo')
            ->setEntityLabelInPlural('Activos')
            ->setPageTitle('index', 'Listado General de Activos')
            ->setSearchFields(['name', 'brand', 'serial', 'assetNumber', 'nationalInventoryNumber', 'currentHolder'])
            ->setFormOptions(['csrf_protection' => false]);
    }

    public function configureActions(Actions $actions): Actions
    {
        $exportarExcel = Action::new('exportarExcel', 'Excel', 'fa fa-file-excel')
            ->linkToCrudAction('exportarExcel')
            ->createAsGlobalAction()
            ->setCssClass('btn btn-success');

        $exportarPdf = Action::new('exportarPdf', 'PDF', 'fa fa-file-pdf')
            ->linkToCrudAction('exportarPdf')
            ->createAsGlobalAction()
            ->setCssClass('btn btn-danger');

        return $actions
            ->add(Crud::PAGE_INDEX, $exportarExcel)
            ->add(Crud::PAGE_INDEX, $exportarPdf)
            ->setPermission(Action::NEW, 'ROLE_ADMIN')
            ->setPermission(Action::DELETE, 'ROLE_ADMIN');
    }

    /**
     * EXPORTACIÓN FILTRADA A EXCEL
     */
    public function exportarExcel(AdminContext $context): StreamedResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $repo = $this->container->get('doctrine')->getRepository(Asset::class);

        // Si NO es Admin, filtramos los activos que van al Excel
        if (!$this->isGranted('ROLE_ADMIN') && $user->getDepartment()) {
            $assets = $repo->findBy(['currentDepartment' => $user->getDepartment()]);
        } elseif ($this->isGranted('ROLE_ADMIN')) {
            $assets = $repo->findAll();
        } else {
            $assets = [];
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Inventario');

        $headers = ['ID', 'Nombre', 'Marca', 'Serie', 'ID Local', 'Estado', 'Departamento', 'Responsable'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);

        $rowCount = 2;
        foreach ($assets as $asset) {
            $sheet->setCellValue('A' . $rowCount, $asset->getId());
            $sheet->setCellValue('B' . $rowCount, $asset->getName());
            $sheet->setCellValue('C' . $rowCount, $asset->getBrand());
            $sheet->setCellValue('D' . $rowCount, $asset->getSerial());
            $sheet->setCellValue('E' . $rowCount, $asset->getAssetNumber());
            $sheet->setCellValue('F' . $rowCount, $asset->getStatus());
            $sheet->setCellValue('G' . $rowCount, $asset->getCurrentDepartment() ? $asset->getCurrentDepartment()->getName() : 'N/A');
            $sheet->setCellValue('H' . $rowCount, $asset->getCurrentHolder());
            $rowCount++;
        }

        foreach (range('A', 'H') as $col) { $sheet->getColumnDimension($col)->setAutoSize(true); }

        $writer = new Xlsx($spreadsheet);
        return new StreamedResponse(function () use ($writer) { $writer->save('php://output'); }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="Inventario_' . date('d-m-Y') . '.xlsx"',
        ]);
    }

    /**
     * EXPORTACIÓN FILTRADA A PDF
     */
    public function exportarPdf(AdminContext $context): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $repo = $this->container->get('doctrine')->getRepository(Asset::class);

        if (!$this->isGranted('ROLE_ADMIN') && $user->getDepartment()) {
            $assets = $repo->findBy(['currentDepartment' => $user->getDepartment()]);
        } elseif ($this->isGranted('ROLE_ADMIN')) {
            $assets = $repo->findAll();
        } else {
            $assets = [];
        }

        $html = $this->renderView('admin/pdf_report.html.twig', [
            'assets' => $assets,
            'fecha' => date('d/m/Y H:i')
        ]);

        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Reporte_Inventario.pdf"',
        ]);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id', 'ID')->onlyOnDetail(),
            TextField::new('name', 'Nombre'),
            TextField::new('brand', 'Marca'),
            TextField::new('serial', 'S/N'),
            TextField::new('currentHolder', 'Responsable Actual'),
            ChoiceField::new('category', 'Categoría')
                ->setChoices([
                    'Cómputo' => 'COMPUTO',
                    'Mobiliario' => 'MOBILIARIO',
                    'Vehículo' => 'VEHICULO',
                    'Otros' => 'OTRO'
                ]),
            ChoiceField::new('status', 'Estado')->setChoices([
                'Excelente' => 'Excelente', 'Bueno' => 'Bueno', 'Regular' => 'Regular',
                'En Reparación' => 'En Reparación', 'Desuso' => 'Desuso'
            ])->renderAsBadges(),
            AssociationField::new('currentDepartment', 'Ubicación'),
            DateTimeField::new('updatedAt', 'Actualizado')->onlyOnIndex(),
        ];
    }
}
