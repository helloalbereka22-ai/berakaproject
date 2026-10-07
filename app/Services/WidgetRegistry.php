<?php

namespace App\Services;

class WidgetRegistry
{
    /**
     * @var array
     */
    protected $widgets = [];

    /**
     * Register a new widget.
     *
     * @param string $key Unique identifier for the widget
     * @param string $component The Livewire component name (e.g., 'admin.widgets.global-kpi-cards')
     * @param array $roles Roles allowed to see this widget
     * @param array $businessTypes Business types allowed to see this widget (empty means all)
     * @param int $order Display order
     * @param string $size CSS Grid span class (e.g. 'col-span-1', 'col-span-2')
     */
    public function register(string $key, string $component, array $roles, array $businessTypes = [], int $order = 0, string $size = 'col-span-1')
    {
        $this->widgets[$key] = [
            'component' => $component,
            'roles' => $roles,
            'businessTypes' => $businessTypes,
            'order' => $order,
            'size' => $size,
        ];
    }

    /**
     * Get widgets for the given user.
     *
     * @param \App\User $user
     * @param string|null $currentBusinessType
     * @return array
     */
    public function getWidgetsForUser($user, ?string $currentBusinessType = null): array
    {
        $userRoles = $user->roles->pluck('name')->toArray();
        // If the user doesn't have roles related relations eagerly loaded, fallback to checking directly
        if (empty($userRoles) && method_exists($user, 'getRoleNames')) {
            $userRoles = $user->getRoleNames()->toArray();
        }

        $filtered = array_filter($this->widgets, function ($widget) use ($userRoles, $currentBusinessType) {
            // Check roles
            $hasRole = !empty(array_intersect($widget['roles'], $userRoles));
            
            // Allow Super Admin to see everything if needed, but here we strictly respect the registered roles
            // to ensure they have their own command center widgets instead of all branch widgets.
            
            // Check business type if specified
            $hasBusinessType = empty($widget['businessTypes']) || in_array($currentBusinessType, $widget['businessTypes']);
            
            return $hasRole && $hasBusinessType;
        });

        // Sort by order
        uasort($filtered, function ($a, $b) {
            return $a['order'] <=> $b['order'];
        });

        return $filtered;
    }
}
