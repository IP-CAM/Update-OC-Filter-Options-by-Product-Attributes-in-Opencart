<?php
class ModelCatalogProductToOcFilterService extends Model {
    private $attributes;
    private $oldOptions;
    private $filterData;

  public function addProductToFilter($productId, $filterdata)
  {
      $this->load->model('catalog/ocfilter');

      $this->filterData =$filterdata;
      $this->attributes = $filterdata['product_attribute'];
      $this->oldOptions = $filterdata['ocfilter_product_option'];

      ksort($this->oldOptions);
      $this->UpdateProductAttributes($productId);

  }

  protected function UpdateProductAttributes($productId)
  {


        $updatedOptions = $this->getOptionsByAttributes($this->attributes);

        foreach ($updatedOptions as $optionId=>$option){
            foreach ($option['values'] as $value){
                $this->updateValue($optionId, $value['selected'],$productId);
            }
        }

  }

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

    private function updateValue($optionId,$valueId,$productId)
    {

    }
}
?>