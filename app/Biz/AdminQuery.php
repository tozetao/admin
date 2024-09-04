<?php

namespace App\Biz;

use App\Models\User;
use App\Repository\AdminRepository;
use App\Resources\AdminResource;
use Illuminate\Support\Collection;

class AdminQuery
{
    const ViewMy = 'my';
    const ViewAll = 'all';

    private $defaultResult;
    private $adminRepository;
    private $adminResource;

    public function __construct(AdminRepository $adminRepository, AdminResource $adminResource)
    {
        $this->defaultResult = [
            'count' => 0,
            'models' => []
        ];
        $this->adminResource = $adminResource;
        $this->adminRepository = $adminRepository;
    }

    // $id = 当前管理员id
    // 查询管理员列表
    // 对于中央服管理员，他可以查询每个服的管理员，也可以查询自己服的管理员。
    // 对于子服管理员，只能够查询到自己服的管理员。
    public function execute(User $user, string $viewBy, string $keyword, $status, int $page, int $limit): ?array
    {
        $id = $user->getAuthIdentifier();
        $offset = ($page - 1) * $limit;

        // 查询我的账号
        if (self::ViewMy == $viewBy) {
            $models = $this->adminRepository->findMore($keyword, $id, $status, $offset, $limit);
            $models = $this->with($models);
            $count = $this->adminRepository->countFindMore($keyword, $id, $status);
            return [
                'count' => $count,
                'models' => $this->adminResource->collection($models)
            ];
        }

        // 查询所有子孙账号
        return $this->findDescendants($keyword, $id, $status, $offset, $limit);
    }

    // 查找子孙账号
    public function findDescendants(string $keyword, int $id, $status, $offset, $limit): ?array
    {
        $allDescendants = $this->adminRepository->findAllDescendants($id, ['id', 'pid', 'status']);

        if (!$allDescendants) {
            return $this->defaultResult;
        }

        // 搜索单个管理员
        if ($keyword) {
            return $this->keywordQuery($allDescendants, $keyword, $status);
        }

        $collection = collect($allDescendants)->sortByDesc('id');

        // 状态过滤
        if (is_int_number($status)) {
            $allDescendants = $collection
                ->filter(function ($model) use($status) {
                return $model->status == $status;
            })->all();
        }

        // 分页，然后找出单独的数据。
        $items = array_splice($allDescendants, $offset, $limit);
        $models = $this->adminRepository->findIn(array_column($items, 'id'))->sortByDesc('id');
        $models = $this->with($models);

        return [
            'models' => $this->adminResource->collection($models),
            'count' => count($allDescendants)
        ];
    }

    private function keywordQuery(?array $allDescendants, string $account, $status): array
    {
        $ids = array_column($allDescendants, 'id');
        $admin = $this->adminRepository->findOneBy([
            'account' => $account,
            'status' => $status
        ]);
        if (empty($admin) || !in_array($admin->id, $ids)) {
            return $this->defaultResult;
        }
        return [
            'count' => 1,
            'models' => [$this->adminResource->one($admin)]
        ];
    }

    private function with(Collection $collection): Collection
    {
        $ids = $collection->pluck('pid')->all();
        $parents = $this->adminRepository->findIn($ids);

        return $collection->map(function (User $admin) use($parents) {
            $parent = $parents->where('id', $admin->pid)->first();
            if ($parent) {
                $admin->parent = $parent;
            } else {
                $admin->parent = null;
            }
            return $admin;
        });
    }
}
