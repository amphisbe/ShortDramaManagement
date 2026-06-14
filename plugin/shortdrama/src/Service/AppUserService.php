<?php

declare(strict_types=1);

namespace Plugin\ShortDrama\Service;

use App\Exception\BusinessException;
use App\Http\Common\ResultCode;
use Plugin\ShortDrama\Contract\AppUserRepositoryInterface;
use Plugin\ShortDrama\Model\AppUser;

final class AppUserService
{
    public function __construct(private readonly AppUserRepositoryInterface $repository) {}

    public function page(array $params, ?int $page = null, ?int $pageSize = null): array
    {
        return $this->repository->page($params, $page, $pageSize);
    }

    public function find(int $id): AppUser
    {
        return $this->repository->findById($id)
            ?? throw new BusinessException(ResultCode::NOT_FOUND, '用户不存在');
    }

    public function updateStatus(int $id, int $status): void
    {
        if (! in_array($status, [AppUser::STATUS_DISABLED, AppUser::STATUS_NORMAL], true)) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '用户状态不合法');
        }
        $this->find($id);
        $this->repository->updateStatus($id, $status);
    }
}
