<?php
namespace Exam\Oms\Model\Resolver;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Api\SearchCriteriaFactory;
use Magento\Framework\Api\SearchCriteriaInterface;

class ProductHaryo implements ResolverInterface
{
    private $productRepository;
    private $searchCriteriaBuilder;
    private $filterBuilder;
    private $searchCriteriaFactory;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SearchCriteriaFactory $searchCriteriaFactory,
        FilterBuilder $filterBuilder
    ) {
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaFactory = $searchCriteriaFactory;
    }

    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        
        if (isset($args['search']) && !empty($args['search'])) {
            $filter = $this->filterBuilder
                ->setField('name')
                ->setValue('%' . $args['search'] . '%')
                ->setConditionType('like')
                ->create();

            $this->searchCriteriaBuilder->addFilters([$filter]);
        }

        if (isset($args['filter']) && !empty($args['filter'])) {
            foreach ($args['filter'] as $field => $condition) {
                foreach ($condition as $conditionType => $value) {
                    $filter = $this->filterBuilder
                        ->setField($field)
                        ->setValue($value)
                        ->setConditionType($conditionType)
                        ->create();

                    $this->searchCriteriaBuilder->addFilters([$filter]);
                }
            }
        }

        if (isset($args['sort']) && !empty($args['sort'])) {
            $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
            $sortOrderBuilder = $objectManager->get('\Magento\Framework\Api\SortOrderBuilder');
            foreach ($args['sort'] as $field => $direction) {
                $sortOrder = $sortOrderBuilder
                    ->setField($field)
                    ->setDirection($direction)
                    ->create();

                $this->searchCriteriaBuilder->addSortOrder($sortOrder);
            }
        }

        if (isset($args['pageSize'])) {
            $this->searchCriteriaBuilder->setPageSize($args['pageSize']);
        }

        if (isset($args['currentPage'])) {
            $this->searchCriteriaBuilder->setCurrentPage($args['currentPage']);
        }

        $searchCriteria = $this->searchCriteriaBuilder->create();

        $products = $this->productRepository->getList($searchCriteria);

        $items = [];
        foreach ($products->getItems() as $product) {
            $items[] = [
                'entity_id' => $product->getId(),
                'sku' => $product->getSku(),
                'name' => $product->getName(),
                'price' =>  $product->getPrice(),
                'status' => $product->getStatus(),
                'description' => $product->getDescription(),
                'short_description' => $product->getShortDescription(),
                'weight' => $product->getWeight(),
                'dimension_package_height' => $product->getCustomAttribute('dimension_package_height') ? $product->getCustomAttribute('dimension_package_height')->getValue() : null,
                'dimension_package_length' => $product->getCustomAttribute('dimension_package_length') ? $product->getCustomAttribute('dimension_package_length')->getValue() : null,
                'dimension_package_width' => $product->getCustomAttribute('dimension_package_width') ? $product->getCustomAttribute('dimension_package_width')->getValue() : null
            ];
        }

        return [
            'items' => $items,
            'page_info' => [
                'current_page' => $products->getSearchCriteria()->getCurrentPage(),
                'page_size' => $products->getSearchCriteria()->getPageSize(),
                'total_pages' => ceil($products->getTotalCount() / $products->getSearchCriteria()->getPageSize())
            ],
            'total_count' => $products->getTotalCount()
        ];
    }
}
