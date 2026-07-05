<?php
declare(strict_types=1);

namespace AdminConnectAPI;

use Cake\Core\BasePlugin;
use Cake\Routing\RouteBuilder;

class AdminConnectAPIPlugin extends BasePlugin
{
    public function routes(RouteBuilder $routes): void
    {
        $routes->plugin(
            'AdminConnectAPI',
            ['path' => '/api-admin'],
            function (RouteBuilder $builder) {
                $builder->connect('/', ['controller' => 'AdminUsers', 'action' => 'index']);

                $builder->resources('AdminUsers', ['path' => 'users']);
                $builder->post('/users/:uuid/activate', ['controller' => 'AdminUsers', 'action' => 'activate']);
                $builder->post('/users/:uuid/deactivate', ['controller' => 'AdminUsers', 'action' => 'deactivate']);
                $builder->post('/users/:uuid/send-password-reset', ['controller' => 'AdminUsers', 'action' => 'sendPasswordReset']);
                $builder->resources('AdminCampaigns', ['path' => 'campaigns']);
                $builder->post('/campaigns/:campaignUuid/assign-puzzle/:puzzleUuid', ['controller' => 'AdminCampaigns', 'action' => 'assignPuzzle']);
                $builder->resources('AdminPuzzles', ['path' => 'puzzles']);
                $builder->get('/puzzles/:uuid/image', ['controller' => 'AdminPuzzles', 'action' => 'image']);
                $builder->post('/puzzles/:uuid/assign-campaign', ['controller' => 'AdminPuzzles', 'action' => 'assignCampaign']);
                $builder->post('/puzzles/:uuid/remove-campaign/:campaignUuid', ['controller' => 'AdminPuzzles', 'action' => 'removeCampaign']);
                $builder->post('/puzzles/:uuid/update', ['controller' => 'AdminPuzzles', 'action' => 'update']);
                $builder->resources('AdminGroups', ['path' => 'groups']);

                $builder->fallbacks();
            }
        );
        parent::routes($routes);
    }
}
