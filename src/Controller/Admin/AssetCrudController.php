<?php

namespace App\Controller\Admin;

use App\Entity\Asset;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
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

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Activo')
            ->setEntityLabelInPlural('Activos')
            ->setPageTitle('index', 'Listado General de Activos')
            ->setPageTitle('new', 'Registrar Nuevo Activo')
            ->setPageTitle('edit', 'Modificar Información del Activo')
            ->setPageTitle('detail', 'Información Detallada del Activo')
            ->setSearchFields(['name', 'brand', 'serial', 'assetNumber', 'nationalInventoryNumber'])
            ->setFormOptions([
                'csrf_protection' => false,
            ]);
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
            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action->setLabel('Añadir Activo');
            })
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action->setLabel('Editar');
            })
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action->setLabel('Eliminar');
            });
    }

    public function exportarExcel(AdminContext $context): StreamedResponse
    {
        $assets = $this->container->get('doctrine')->getRepository(Asset::class)->findAll();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Inventario');

        $headers = ['ID', 'Nombre', 'Marca', 'Serie', 'ID Local', 'Estado', 'Departamento'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);

        $rowCount = 2;
        foreach ($assets as $asset) {
            $sheet->setCellValue('A' . $rowCount, $asset->getId());
            $sheet->setCellValue('B' . $rowCount, $asset->getName());
            $sheet->setCellValue('C' . $rowCount, $asset->getBrand());
            $sheet->setCellValue('D' . $rowCount, $asset->getSerial());
            $sheet->setCellValue('E' . $rowCount, $asset->getAssetNumber());
            $sheet->setCellValue('F' . $rowCount, $asset->getStatus());
            $sheet->setCellValue('G' . $rowCount, $asset->getCurrentDepartment() ? $asset->getCurrentDepartment()->getName() : 'N/A');
            $rowCount++;
        }

        foreach (range('A', 'G') as $col) { $sheet->getColumnDimension($col)->setAutoSize(true); }

        $writer = new Xlsx($spreadsheet);
        return new StreamedResponse(function () use ($writer) { $writer->save('php://output'); }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="Inventario_' . date('d-m-Y') . '.xlsx"',
        ]);
    }

    public function exportarPdf(AdminContext $context): Response
    {
        $assets = $this->container->get('doctrine')->getRepository(Asset::class)->findAll();

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
            TextField::new('assetNumber', 'ID Local')->hideOnIndex(),
            TextField::new('nationalInventoryNumber', 'ID Nacional')->hideOnIndex(),
            ChoiceField::new('status', 'Estado')->setChoices([
                'Excelente' => 'Excelente', 'Bueno' => 'Bueno', 'Regular' => 'Regular',
                'En Reparación' => 'En Reparación', 'Desuso' => 'Desuso'
            ])->renderAsBadges(),
            AssociationField::new('currentDepartment', 'Ubicación'),
            DateTimeField::new('updatedAt', 'Actualizado')->onlyOnIndex(),
        ];
    }
}
