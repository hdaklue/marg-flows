<?php

declare(strict_types=1);

namespace App\Services\Directory\Contracts;

/**
 * System Directory Manager Contract.
 *
 * Defines the interface for managing system-wide storage operations
 * that are not tenant-specific, such as avatars and temporary files.
 */
interface SystemDirectoryManagerContract
{
    /**
     * Get avatar storage strategy for user profile images.
     *
     * Avatar storage is system-wide and not tenant-specific.
     *
     * @return StorageStrategyContract Configured avatar storage strategy
     */
    public function avatars(): StorageStrategyContract;

    /**
     * Get temporary storage strategy for transient files.
     *
     * @return StorageStrategyContract Configured temporary storage strategy
     */
    public function temp(): StorageStrategyContract;
}
