<?php

namespace Drupal\adminic_toolbar;

use Drupal\Core\Menu\MenuTreeParameters;

class ToolbarConfigFromMenu {

  public function getConfig($menuName = 'admin') {
    $configs = [];
    $menuTree = $this->getMenuTree($menuName);
    $configs['primary_sections'] = $this->getPrimarySections($menuTree);
    $configs['primary_sections_tabs'] = $this->getPrimarySectionsTabs($menuTree, $configs['primary_sections']);
    return $configs;
  }

  protected function getMenuTree($menuName) {
    $menu_tree = \Drupal::menuTree();
    $parameters = new MenuTreeParameters();
    $parameters->setMaxDepth(4);
    $tree = $menu_tree->load($menuName, $parameters);

    $manipulators = array(
      // Only show links that are accessible for the current user.
      array('callable' => 'menu.default_tree_manipulators:checkAccess'),
      // Use the default sorting of menu links.
      array('callable' => 'menu.default_tree_manipulators:generateIndexAndSort'),
    );
    $tree = $menu_tree->transform($tree, $manipulators);

    return $tree;
  }

  protected function getPrimarySections($menuTree) {
    $primarySections = [];
    $root = reset($menuTree);
    /** @var \Drupal\Core\Menu\MenuLinkDefault $link */
    $link = $root->link;
    $primarySections[] = ['id' => $link->getMenuName()];
    return $primarySections;
  }

  protected function getPrimarySectionsTabs($menuTree, $primaryTabs) {
    $primarySections = reset($primaryTabs);
    $primarySectionId = reset($primarySections);

    $root = reset($menuTree);
    $subtree = $root->subtree;

    $tabs = [];
    foreach ($subtree as $menuTreeelement) {
      /** @var \Drupal\Core\Menu\MenuLinkDefault $link */
      $link = $menuTreeelement->link;

      $id = $link->getRouteName();
      $routeName = $link->getRouteName();
      $routeParameters = $link->getRouteParameters();
      $tabs[] = [
        'id' => $id,
        'primary_section_id' => $primarySectionId,
        'route_name' => $routeName,
        'route_parameters' => $routeParameters,
      ];

    }
    return $tabs;
  }

}
