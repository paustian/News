<?php
/**
 * News.
 *
 * @copyright Michael Ueberschaer (MU)
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @author Michael Ueberschaer <info@homepages-mit-zikula.de>.
 * @link https://homepages-mit-zikula.de
 * @link http://zikula.org
 * @version Generated by ModuleStudio (https://modulestudio.de).
 */

namespace MU\NewsModule\ContentType\Base;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use MU\NewsModule\Helper\FeatureActivationHelper;

/**
 * Generic item list content plugin base class.
 */
abstract class AbstractItemList extends \Content_AbstractContentType implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * The treated object type.
     *
     * @var string
     */
    protected $objectType;
    
    /**
     * The sorting criteria.
     *
     * @var string
     */
    protected $sorting;
    
    /**
     * The amount of desired items.
     *
     * @var integer
     */
    protected $amount;
    
    /**
     * Name of template file.
     *
     * @var string
     */
    protected $template;
    
    /**
     * Name of custom template file.
     *
     * @var string
     */
    protected $customTemplate;
    
    /**
     * Optional filters.
     *
     * @var string
     */
    protected $filter;
    
    /**
     * List of object types allowing categorisation.
     *
     * @var array
     */
    protected $categorisableObjectTypes;
    
    /**
     * List of category registries for different trees.
     *
     * @var array
     */
    protected $catRegistries;
    
    /**
     * List of category properties for different trees.
     *
     * @var array
     */
    protected $catProperties;
    
    /**
     * List of category ids with sub arrays for each registry.
     *
     * @var array
     */
    protected $catIds;
    
    /**
     * ItemList constructor.
     */
    public function __construct()
    {
        $this->setContainer(\ServiceUtil::getManager());
    }
    
    /**
     * Returns the module providing this content type.
     *
     * @return string The module name
     */
    public function getModule()
    {
        return 'MUNewsModule';
    }
    
    /**
     * Returns the name of this content type.
     *
     * @return string The content type name
     */
    public function getName()
    {
        return 'ItemList';
    }
    
    /**
     * Returns the title of this content type.
     *
     * @return string The content type title
     */
    public function getTitle()
    {
        return $this->container->get('translator.default')->__('MUNewsModule list view');
    }
    
    /**
     * Returns the description of this content type.
     *
     * @return string The content type description
     */
    public function getDescription()
    {
        return $this->container->get('translator.default')->__('Display list of MUNewsModule objects.');
    }
    
    /**
     * Loads the data.
     *
     * @param array $data Data array with parameters
     */
    public function loadData(&$data)
    {
        $controllerHelper = $this->container->get('mu_news_module.controller_helper');
    
        $contextArgs = ['name' => 'list'];
        if (!isset($data['objectType']) || !in_array($data['objectType'], $controllerHelper->getObjectTypes('contentType', $contextArgs))) {
            $data['objectType'] = $controllerHelper->getDefaultObjectType('contentType', $contextArgs);
        }
    
        $this->objectType = $data['objectType'];
    
        $this->sorting = isset($data['sorting']) ? $data['sorting'] : 'default';
        $this->amount = isset($data['amount']) ? $data['amount'] : 1;
        $this->template = isset($data['template']) ? $data['template'] : 'itemlist_' . $this->objectType . '_display.html.twig';
        $this->customTemplate = isset($data['customTemplate']) ? $data['customTemplate'] : '';
        $this->filter = isset($data['filter']) ? $data['filter'] : '';
        $featureActivationHelper = $this->container->get('mu_news_module.feature_activation_helper');
        if ($featureActivationHelper->isEnabled(FeatureActivationHelper::CATEGORIES, $this->objectType)) {
            $this->categorisableObjectTypes = ['message'];
            $categoryHelper = $this->container->get('mu_news_module.category_helper');
    
            // fetch category properties
            $this->catRegistries = [];
            $this->catProperties = [];
            if (in_array($this->objectType, $this->categorisableObjectTypes)) {
                $entityFactory = $this->container->get('mu_news_module.entity_factory');
                $idField = $entityFactory->getIdField($this->objectType);
                $this->catRegistries = $categoryHelper->getAllPropertiesWithMainCat($this->objectType, $idField);
                $this->catProperties = $categoryHelper->getAllProperties($this->objectType);
            }
    
            if (!isset($data['catIds'])) {
                $primaryRegistry = $categoryHelper->getPrimaryProperty($this->objectType);
                $data['catIds'] = [$primaryRegistry => []];
                // backwards compatibility
                if (isset($data['catId'])) {
                    $data['catIds'][$primaryRegistry][] = $data['catId'];
                    unset($data['catId']);
                }
            } elseif (!is_array($data['catIds'])) {
                $data['catIds'] = explode(',', $data['catIds']);
            }
    
            foreach ($this->catRegistries as $registryId => $registryCid) {
                $propName = '';
                foreach ($this->catProperties as $propertyName => $propertyId) {
                    if ($propertyId == $registryId) {
                        $propName = $propertyName;
                        break;
                    }
                }
                $data['catIds'][$propName] = [];
                if (isset($data['catids' . $propName])) {
                    $data['catIds'][$propName] = $data['catids' . $propName];
                }
                if (!is_array($data['catIds'][$propName])) {
                    if ($data['catIds'][$propName]) {
                        $data['catIds'][$propName] = [$data['catIds'][$propName]];
                    } else {
                        $data['catIds'][$propName] = [];
                    }
                }
            }
    
            $this->catIds = $data['catIds'];
        }
    }
    
    /**
     * Displays the data.
     *
     * @return string The returned output
     */
    public function display()
    {
        $repository = $this->container->get('mu_news_module.entity_factory')->getRepository($this->objectType);
        $permissionApi = $this->container->get('zikula_permissions_module.api.permission');
    
        // create query
        $orderBy = $this->container->get('mu_news_module.model_helper')->resolveSortParameter($this->objectType, $this->sorting);
        $qb = $repository->genericBaseQuery($this->filter, $orderBy);
    
        $featureActivationHelper = $this->container->get('mu_news_module.feature_activation_helper');
        if ($featureActivationHelper->isEnabled(FeatureActivationHelper::CATEGORIES, $this->objectType)) {
            // apply category filters
            if (in_array($this->objectType, $this->categorisableObjectTypes)) {
                if (is_array($this->catIds) && count($this->catIds) > 0) {
                    $categoryHelper = $this->container->get('mu_news_module.category_helper');
                    $qb = $categoryHelper->buildFilterClauses($qb, $this->objectType, $this->catIds);
                }
            }
        }
    
        // get objects from database
        $currentPage = 1;
        $resultsPerPage = isset($this->amount) ? $this->amount : 1;
        $query = $repository->getSelectWherePaginatedQuery($qb, $currentPage, $resultsPerPage);
        try {
            list($entities, $objectCount) = $repository->retrieveCollectionResult($query, true);
        } catch (\Exception $exception) {
            $entities = [];
            $objectCount = 0;
        }
    
        if ($featureActivationHelper->isEnabled(FeatureActivationHelper::CATEGORIES, $this->objectType)) {
            $entities = $categoryHelper->filterEntitiesByPermission($entities);
        }
    
        $data = [
            'objectType' => $this->objectType,
            'catids' => $this->catIds,
            'sorting' => $this->sorting,
            'amount' => $this->amount,
            'template' => $this->template,
            'customTemplate' => $this->customTemplate,
            'filter' => $this->filter
        ];
    
        $templateParameters = [
            'vars' => $data,
            'objectType' => $this->objectType,
            'items' => $entities
        ];
    
        if ($featureActivationHelper->isEnabled(FeatureActivationHelper::CATEGORIES, $this->objectType)) {
            $templateParameters['registries'] = $this->catRegistries;
            $templateParameters['properties'] = $this->catProperties;
        }
    
        $templateParameters = $this->container->get('mu_news_module.controller_helper')->addTemplateParameters($this->objectType, $templateParameters, 'contentType', []);
    
        $template = $this->getDisplayTemplate();
    
        return $this->container->get('twig')->render($template, $templateParameters);
    }
    
    /**
     * Returns the template used for output.
     *
     * @return string the template path
     */
    protected function getDisplayTemplate()
    {
        $templateFile = $this->template;
        if ($templateFile == 'custom') {
            $templateFile = $this->customTemplate;
        }
    
        $templateForObjectType = str_replace('itemlist_', 'itemlist_' . $this->objectType . '_', $templateFile);
        $templating = $this->container->get('templating');
    
        $templateOptions = [
            'ContentType/' . $templateForObjectType,
            'ContentType/' . $templateFile,
            'ContentType/itemlist_display.html.twig'
        ];
    
        $template = '';
        foreach ($templateOptions as $templatePath) {
            if ($templating->exists('@MUNewsModule/' . $templatePath)) {
                $template = '@MUNewsModule/' . $templatePath;
                break;
            }
        }
    
        return $template;
    }
    
    /**
     * Displays the data for editing.
     */
    public function displayEditing()
    {
        return $this->display();
    }
    
    /**
     * Returns the default data.
     *
     * @return array Default data and parameters
     */
    public function getDefaultData()
    {
        return [
            'objectType' => 'message',
            'sorting' => 'default',
            'amount' => 1,
            'template' => 'itemlist_display.html.twig',
            'customTemplate' => '',
            'filter' => ''
        ];
    }
    
    /**
     * Executes additional actions for the editing mode.
     */
    public function startEditing()
    {
        // ensure that the view does not look for templates in the Content module (#218)
        $this->view->toplevelmodule = 'MUNewsModule';
    
        // ensure our custom plugins are loaded
        array_push($this->view->plugins_dir, 'modules/MU/NewsModule/Resources/views/plugins');
    
        $featureActivationHelper = $this->container->get('mu_news_module.feature_activation_helper');
        if ($featureActivationHelper->isEnabled(FeatureActivationHelper::CATEGORIES, $this->objectType)) {
            // assign category data
            $this->view->assign('registries', $this->catRegistries)
                       ->assign('properties', $this->catProperties);
    
            // assign categories lists for simulating category selectors
            $translator = $this->container->get('translator.default');
            $locale = $this->container->get('request_stack')->getCurrentRequest()->getLocale();
            $categories = [];
            $categoryRepository = $this->container->get('zikula_categories_module.category_repository');
            foreach ($this->catRegistries as $registryId => $registryCid) {
                $propName = '';
                foreach ($this->catProperties as $propertyName => $propertyId) {
                    if ($propertyId == $registryId) {
                        $propName = $propertyName;
                        break;
                    }
                }
    
                $mainCategory = $categoryRepository->find($registryCid);
                $queryBuilder = $categoryRepository->getChildrenQueryBuilder($registryCid);
                $cats = $queryBuilder->getQuery()->execute();
                $catsForDropdown = [
                    [
                        'value' => '',
                        'text' => $translator->__('All')
                    ]
                ];
                foreach ($cats as $category) {
                    $indent = str_repeat('--', $category->getLvl() - $mainCategory()->getLvl() - 1);
                    $categoryName = (!empty($indent) ? '|' : '') . $indent . $category->getName();
                    $catsForDropdown[] = [
                        'value' => $category->getId(),
                        'text' => $categoryName
                    ];
                }
                $categories[$propName] = $catsForDropdown;
            }
    
            $this->view->assign('categories', $categories)
                       ->assign('categoryHelper', $this->container->get('mu_news_module.category_helper'));
        }
        $this->view->assign('featureActivationHelper', $featureActivationHelper)
                   ->assign('objectType', $this->objectType);
    }
    
    /**
     * Returns the edit template path.
     *
     * @return string
     */
    public function getEditTemplate()
    {
        $absoluteTemplatePath = str_replace('ContentType/Base/AbstractItemList.php', 'Resources/views/ContentType/itemlist_edit.tpl', __FILE__);
    
        return 'file:' . $absoluteTemplatePath;
    }
}
