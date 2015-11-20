<?PHP

namespace Openbizx\Easy\Element;

use Openbizx\Core\Expression;
use Openbizx\Easy\Element\LabelText;

class LabelTextPaging extends LabelText
{

    public $currentCss;
    public $currentPage;
    public $totalPage;

    protected function readMetaData(&$xmlArr)
    {
        parent::readMetaData($xmlArr);
        $this->currentCss = isset($xmlArr["ATTRIBUTES"]["CURRENTCSSCLASS"]) ? $xmlArr["ATTRIBUTES"]["CURRENTCSSCLASS"] : null;
        $this->currentPage = isset($xmlArr["ATTRIBUTES"]["CURRENTPAGE"]) ? $xmlArr["ATTRIBUTES"]["CURRENTPAGE"] : null;
        $this->totalPage = isset($xmlArr["ATTRIBUTES"]["TOTALPAGE"]) ? $xmlArr["ATTRIBUTES"]["TOTALPAGE"] : null;
    }

    public function render()
    {
        $formobj = $this->getFormObj();
        $this->totalPage = Expression::evaluateExpression($this->totalPage, $formobj);
        $this->currentPage = Expression::evaluateExpression($this->currentPage, $formobj);

        $style = $this->getStyle();
        $id = $this->objectName;
        $func = $this->getFunction();
        $sHTML = "";
        $link = $this->getLink();
        $target = $this->getTarget();

        for ($i = 1; $i < $this->totalPage + 1; $i++) {
            if ($i == $this->currentPage) {
                $sHTML .= "<a id=\"$id\" href=\"" . $link . $i . "\" $target $func class=\"" . $this->currentCss . "\">" . $i . "</a>";
            } else {
                $sHTML .= "<a id=\"$id\" href=\"" . $link . $i . "\" $target $func $style>" . $i . "</a>";
            }
        }

        return $sHTML;
    }

}

?>