<?php

namespace App\Controller\Admin;

use App\Entity\Asset;
use App\Entity\Department;
use App\Entity\User;
use App\Entity\AssetTransaction;
use App\Repository\AssetRepository;
use App\Repository\AssetTransactionRepository;
use App\Repository\DepartmentRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private AssetRepository $assetRepository,
        private AssetTransactionRepository $transactionRepository,
        private DepartmentRepository $departmentRepository
    ) {}

    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        $totalAssets = $this->assetRepository->count([]);

        $inRepair = $this->assetRepository->createQueryBuilder('a')
            ->select('count(a.id)')
            ->where('a.status = :s1 OR a.status = :s2')
            ->setParameter('s1', 'En Reparación')
            ->setParameter('s2', 'Regular')
            ->getQuery()
            ->getSingleScalarResult();

        $recentTransactions = $this->transactionRepository->findBy([], ['createdAt' => 'DESC'], 6);

        $departments = $this->departmentRepository->findAll();
        $chartLabels = [];
        $chartData = [];

        foreach ($departments as $dept) {
            $count = $this->assetRepository->count(['currentDepartment' => $dept]);
            if ($count > 0) {
                $chartLabels[] = $dept->getName();
                $chartData[] = $count;
            }
        }

        return $this->render('admin/dashboard.html.twig', [
            'totalAssets' => $totalAssets,
            'inRepair' => $inRepair,
            'recentTransactions' => $recentTransactions,
            'chartLabels' => $chartLabels,
            'chartData' => $chartData,
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Inventario Institucional')
            // Forzamos el idioma español para los componentes internos
            ->setLocales(['es'])
            ->setTranslationDomain('messages');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Panel de Inicio', 'fa fa-home');

        yield MenuItem::section('Gestión de Activos');
        yield MenuItem::linkToCrud('Listado de Activos', 'fas fa-laptop', Asset::class);
        yield MenuItem::linkToCrud('Departamentos', 'fas fa-building', Department::class);

        yield MenuItem::section('Auditoría y Usuarios');
        yield MenuItem::linkToCrud('Historial de Movimientos', 'fas fa-history', AssetTransaction::class);
        yield MenuItem::linkToCrud('Gestión de Usuarios', 'fas fa-users', User::class);

        yield MenuItem::section('Sistema');
        yield MenuItem::linkToLogout('Cerrar Sesión', 'fa fa-sign-out-alt');
    }
}
