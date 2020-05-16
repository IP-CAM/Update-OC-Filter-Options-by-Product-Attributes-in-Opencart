<?php
class ModelCatalogProductToOcFilterService extends Model {

    private $attributes;
    private $oldOptions=null;
    private $filterData;

  public function addProductToFilter($productId, $filterdata)
  {
      if (!$this->config->get('ocfilter_status')) return;

      $this->load->model('catalog/ocfilter');

      $this->filterData =$filterdata;
      $this->attributes = $filterdata['product_attribute'];

       if (isset($filterdata['ocfilter_product_option'])) {
           $this->oldOptions = $this->makeOptionsData($filterdata['ocfilter_product_option']);
       }

      $this->UpdateProductAttributes($productId);
  }

    /**
     * @param $productId
     * update ocfilter options by product attributes
     */
  private function UpdateProductAttributes($productId)
  {
        $updatedOptions = $this->getOptionsByAttributes($this->attributes);

        foreach ($updatedOptions as $optionId=>$option){
            foreach ($option['values'] as $value){
                if (isset($this->oldOptions[$optionId])){
                    if (!in_array($value['selected'],$this->oldOptions[$optionId]))
                        $this->updateValue($optionId, $value['selected'],$productId);
                }else{
                    $this->updateValue($optionId, $value['selected'],$productId);
                }
            }
        }
  }

    /**
     * @param $productId
     * @return array
     * get ocfilter product options
     */
    private function getProductOptions($productId)
  {
      $optionsData = $this->model_catalog_ocfilter->getProductOCFilterValues($productId);

      $existOptions=[];
      foreach ($optionsData as $key=>$option){
          $existOptions[$key]['values']=[];
                foreach ($option['values'] as $value){
                    $valueId = $value['value_id'];
                    $existOptions[$key]['values'][$valueId]=[
                        'selected' =>$valueId,
                    ];
                }

      }

      ksort($existOptions);

      return $existOptions;
  }

    /**
     * @param $attributes
     * @return array ocfilter options due to product attributes
     */
    private function getOptionsByAttributes ($attributes)
    {
        $options =[];

        foreach ($attributes as $attribute){

            $optionName = trim(preg_replace('/\(.*\)/','',$attribute['name']));

            $optionId = $this->getOptionByName($optionName);

            if ($optionId) {
                $atributesText = $attribute['product_attribute_description'][1]['text'];
                  $valuesNames = array_map('trim', explode(',',$atributesText ));
                  $values = $this->getOptionValues($valuesNames, $optionId);

                $options[$optionId] =[
                    'values'=> $values['values']
                ];
            }

        }

        return $options;
    }

    /**
     * @param $name
     * @return int|null $optionId
     * get OcFilterOption id by attribute name;
     */
    private function getOptionByName($name)
    {
        $sql = "SELECT * FROM proftehb_db.oc_ocfilter_option_description od
                left join oc_ocfilter_option_to_category otc on od.option_id = otc.option_id
                left join oc_ocfilter_option fo on fo.option_id = od.option_id 
                where category_id ='".$this->filterData['main_category_id']."'
                and fo.status = 1
                and name ='". $name."'";
        $result = $this->db->query($sql)->row;

        return $result? intval($result['option_id']):null;
    }

    /**
     * @param $valueNames
     * @param $optionId
     * @return array $values
     */
    private function getOptionValues($valueNames, $optionId)
    {

        $values=array(
                'values' => [],
                );

        foreach ($valueNames as $name){
            $sql = "SELECT value_id FROM " . DB_PREFIX . "ocfilter_option_value_description 
                    WHERE option_id = ".$optionId."
                    AND name = '".$name."'";

            $query = $this->db->query($sql)->row;

            if ($query){
                $valueId = $query['value_id'];
                $values['values'][$valueId]=[
                  'selected' =>  $query['value_id']
                ];
            }

        }

        return $values;
    }

    /**
     * @param $optionId
     * @param $valueId
     * @param $productId
     */
    private function updateValue($optionId,$valueId,$productId)
    {
        $sql = "INSERT INTO " . DB_PREFIX . "ocfilter_option_value_to_product
                       (product_id,option_id,value_id) 
                VALUES(".$productId.",".$optionId.",".$valueId.")";

        $this->db->query($sql);
    }

    /**
     * @param $optionId
     * @param $valueId
     * @param $productId
     * @return bool
     */
    private function isOptionValueInProduct($optionId,$valueId,$productId)
    {
        $sql = "SELECT value_id FROM " . DB_PREFIX . "ocfilter_option_value_to_product
                    where product_id = '".$productId."' 
                    and option_id = '".$optionId."'
                    and value_id = '".$valueId."'";

        $query = $this->db->query($sql)->rows;

        return !empty($query);

    }

    /**
     * @param $productId
     *
     */
    private function clearOCFilterValues($productId)
    {
        $sql = "DELETE FROM " . DB_PREFIX . "ocfilter_option_value_to_product
                    where product_id = '".$productId."'";

        $result = $this->db->query($sql);
    }

    /**
     * @param $options
     * @return array
     * make simple array of ocfilter options data
     */
    private function makeOptionsData($options)
    {
        $optionsData = [];
        ksort($options);
        foreach ($options as $key=>$option){
            $optionsData[$key]=[];
            foreach ($option['values'] as $valueId => $value ){
                $optionsData[$key][]=$valueId;
            }
        }

        return $optionsData;
    }
}

?>