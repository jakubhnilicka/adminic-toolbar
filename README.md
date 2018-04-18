# Adminic Toolbar

**Alternative toolbar for Drupal 8**

![Mobile screenshot](https://github.com/jakubhnilicka/adminic-toolbar/wiki/images/adminic-toolbar-banner.png)

- Modern appearance.
- It is always placed on the left side of your site.
- Configuration and links definition in yml files.
- Possibility to include custom widgets.
- Links are grouped into sections.

# Installation

- Enable the Adminic toolbar modul.
- **In your user profile in Adminic Toolbar section tick 'Use Adminic Toolbar'.**
- Every user can have his own choice of the toolbar he wants to be using. So one can use the classic toolbar, but another can be using the Adminic Toolbar. 
- (It is planed to have this behavior configurable in the settings.)
- If the classic toolbar module is enabled, Adminic Toolbar will disable it.

# Configuration

All content is divided into a primary and a secondary toolbar.
Primary toolbar contains primary sections which contains primary sections tabs.
Secondary toolbar contains secondary sections which contains secondary sections links.

## Sections in primary toolbar

### How to add a new section into the primary toolbar

Add a new 'primary_sections' section to the MODULENAME.toolbar.yml file. Add a section object with the required parameters below. Section ID is required. 

    primary_sections:
    - {id: 'default'}
    - {id: 'general', title: 'General'}

**Parameters**

**id**: ID is required. 

**title**: the title placed above the widget. If not stated, widget will not have a title.

**plugin_id**: custom plugin ID. If not stated, it will appear as a section with a list of tabs.

**weight**: weight to position the section.

**disabled**: set to true to disable the section. If not set to true, the section will be enabled - visible.

### How to edit a section that is already defined

Create a new 'primary_sections' section in the MODULE_NAME.toolbar.yml  file in your module.  Place your widget in there and edit the required information. Section is identified by an id parameter. The section can be disabled by setting the 'disabled' parameter to true. You can change or add a title with the 'title' parameter. You can change its position (order). If you define your new section with an ID of an existing one, the parameters of the already existing section will be rewritten by the new parameters. 

**Examples**

    Disable section
    - {id: 'media.default', tab_id: 'media', disabled: true}
    
    Add or change title
    - {id: 'media.settings', tab_id: 'media', title: 'Configuration'}


## Tabs in primary toolbar

Tabs are basically just links with an icon and they are placed in the primary toolbar. Each tab belongs to some widget and that's why it is important to set an ID of the section the tab belongs to.

### How to add a new tab

Add new 'primary_sections_tabs' section to the MODULENAME.toolbar.yml file. Add a tab object below with the required parameters. For each tab you have to state an ID, 'primary_section_id' the tab is displayed under and a 'route_name' parameter to generate a link. If the route requires any parameters, it is possible to state a route_parameters parameter.

    primary_sections_tabs:
    - {id: 'commerce', primary_section_id: 'commerce', route_name: 'commerce.admin_commerce' }
    - {id: 'add_node', primary_section_id, route_name: 'node.add_page', title: 'Add node', weight: 10}
    - {id: 'add_node_page', primary_section_id, route_name: 'node.add', route_parameters: {node_type: 'page'}}

**Parameters**

**id**: tab ID is required. It is used to generate the classes of the tab in the html markup.

**primary_section_id**: section ID the tab is displayed under. It is required.

**title**: the title of the link. If not set, the title is generated from default route title.

**route_name**: name of the route to generate a tab. It is required.

**route_parameters**: object for the parameters required by the route.

**weight**: weight to position the tab.

**disabled**: set to true to disable the tab. If not set to true, the tab will be enabled - visible.

**badge**: text placed by the oval.

### How to edit a tab that is already defined

You can edit tabs the same way as widgets. Create a 'primary_sections_tabs'  section in the MODULENAME.toolbar.yml file and edit the required information. Tab is identified by an 'id' parameter. The tab can be disabled by setting the 'disabled' parameter to true. You can change or add a title with the 'title' parameter. You can change its position (order) by weight or place it under a different section by the 'primary_section_id' parameter. You can also change the 'route_name' and the 'route_parameters'. If you define a tab with an ID of an existing one, the parameters of the already existing tab  will be rewritten by the new parameters.

**Examples**

    Disable tab
    - {id: 'commerce', primary_section_id: 'commerce', route_name: 'commerce.admin_commerce' , disabled: true} 
    
    Add or change title
    - {id: 'commerce', primary_section_id: 'commerce', route_name: 'commerce.admin_commerce', title: 'Eshop'} 
    
    Change placement of tab
    - {id: 'commerce', primary_section_id: 'media', route_name: 'commerce.admin_commerce' }

## Sections in secondary toolbar

### How to add a new section into the secondary toolbar

Add a new 'secondary_sections' section to the MODULENAME.toolbar.yml file. Add a section object with the required parameters below. Section ID is required. 

    secondary_sections:
    - {id: 'default'}
    - {id: 'general', title: 'General'}
    - {id: 'content.default', tab_id: 'content'}
    - {id: 'media.default', tab_id: 'media'}
    - {id: 'media.settings', tab_id: 'media', title: 'Settings'}

**Parameters**

**id**: section ID is required. 

**tab_id**: ID of the tab the section is displayed under in the secondary toolbar.

**title**: the title placed above the section. If not stated, section will not have a title.

**plugin_id**: custom plugin ID. If not stated, it will appear as a section with a list of links.

**weight**: weight to position the section.

**disabled**: set to true to disable the section. If not set to true, the section will be enabled - visible.

### How to edit a section that is already defined

Create a new 'secondary_sections' section in the MODULE_NAME.toolbar.yml  file in your module.  Place your section in there and edit the required information.  Section is identified by an id parameter. The section can be disabled by setting the 'disabled' parameter to true. You can change or add a title with the 'title' parameter. You can change its position (order) by weight or place it under a different tab by the 'tab_id' parameter. If you define your new section with an ID of an existing one, the parameters of the already existing section will be rewritten by the new parameters. 

**Examples**

    Disable section
    - {id: 'media.default', tab_id: 'media', disabled: true}
    
    Add or change title of section
    - {id: 'media.settings', tab_id: 'media', title: 'Configuration'}
    
    Move section under another tab
    - {id: 'content.default', tab_id: 'media'}
    
## Secondary section links

Links are references placed in the secondary toolbar. Each link belongs to some widget. That's why it is important to state an ID of the section it belongs to.

### How to add a new link

Add a new 'secondary_sections_links' section to MODULENAME.toolbar.yml file. Add a link object below with the required parameters. For each link you have to state a 'secondary_section_id' it is displayed under and a 'route_name' parameter to generate the link. If the route requires any parameters, it is possible to state 'route_parameters' parameter.

    secondary_sections_links:
    - {secondary_section_id: 'content.default', route_name: 'system.admin_content', title: 'Pages'}
    - {secondary_section_id: 'content.default', route_name: 'comment.admin'}
    - {secondary_section_id: 'content.default', route_name: 'view.media.media_page_list'}

**Parameters**

**secondary_section_id**: section ID, the link is displayed under. It is required.

**title**: text displayed as link. If not stated, the text will be generated from the route title. 

**route_name**: the name of the route is required to generate the link.

**route_parameters**: Object for the parameters required by the route.

**weight**: weight to position the link.

**disabled**: set to true to disable the link. If not set to true, the link will be enabled - visible.

**badge**: text placed by the oval.

### How to edit a link that is already defined

You can edit links the same way as sections and tabs. Create a 'secondary_sections_links'  section in the MODULENAME.toolbar.yml file and edit the required information. Link is defined by the id combination. The link can be disabled by setting the 'disabled' parameter to true. You can change or add a title with the 'title' parameter. You can change its position (order) by weight or place it under a different section by the 'secondary_section_id' parameter. You can also change the route_name and the 'route_parameters'. If you define a link with an ID of an existing one, the parameters of the already existing link will be rewritten by the new parameters.

**Examples**

    Disable link
    - {secondary_section_id: 'content.default', route_name: 'comment.admin', disabled: true} 
    
    Change title (text) of link
    - {secondary_section_id: 'content.default', route_name: 'comment.admin', title: 'Eshop'} 
    
    Move link under another section
    - {secondary_section_id: 'structure.default', route_name: 'comment.admin' }
    
# Write custom toolbar plugin

You can write your custom toolbar plugin. User account block in primary toolbar is example of custom toolbar plugin. When you wan't to write your custom toolbar plugin create new class with ToolbarPlugin annotation which implements ToolbarPluginInterface and return render array in getRenderArray() method.

     <?php
     
     /**
      * @ToolbarPlugin(
      *   id = "TOOLBAR_PLUGIN_ID",
      *   name = @Translation("TOOLBAR PLUGIN NAME"),
      * ) 
      */
      class MyCustomToolbarPlugin extends PluginBase implements ToolbarPluginInterface {
      
        /**
         * {@inheritdoc}
         */
        public function getRenderArray() {
          return [
            '#markup' => 'MY CUSTOM TOOLBAR PLUGIN',
          ];
        }
      }
# Toolbar Themes

You can attach different toolbar themes to different drupal themes in toolbar settings. You can create for example your own company theme for toolbar as described bellow.

## Create custom toolbar theme

You can create your own theme for toolbar by creating css file for it and attach library in your theme and module which begins with 'adminic_toolbar.theme'. Youn need to specify name for your theme in library specification. This library is automatically parsed as toolbar library and you can select it in toolbar settings. As base for your css file you can use adminic_toolbar_theme_light.scss file from module. You need to setup correct path for variables and adminic_toolbar_theme sass partials.

**Example**

    adminic_toolbar.theme.light:
      version: VERSION
      name: 'Light'
      css:
        component:
          css/adminic_toolbar_theme_light.css: {preprocess: false}

# API

Loaded configuration can be subsequently changed by hooks.

    hook_toolbar_primary_sections_alter(&$configPrimarySections)
    
    hook_toolbar_primary_sections_tabs_alter(&$configPrimarySectionsTabs)
    
    hook_toolbar_secondary_sections_alter(&$configSecondarySections)
    
    hook_toolbar_secondary_sections_links_alter(&$configSecondarySectionsLinks)
    
# Our hopes for the future

- **Configurable themes** - everyone likes a different color scheme or you need to have the toolbar in the company colors.
- **Sets** - an editor working with media needs to get to some links faster then an editor working with articles. Sets will allow you to switch between predefined links.
- **Environment detection** - I sometimes make a mistake and make changes on the staging server  just because it looks the same as the local web. Some differences in the toolbar will visually distinguish between different environments we work in.
