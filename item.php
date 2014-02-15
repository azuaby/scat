<?
require 'scat.php';
require 'lib/item.php';

$code= $_GET['code'];
$id= (int)$_GET['id'];

if (!$code && !$id) exit;

if (!$id && $code) {
  $r= $db->query("SELECT id FROM item WHERE code = '" .
                 $db->real_escape_string($code) . "'");
  if (!$r) die($m->error);

  if (!$r->num_rows)
      die("<h2>No item found.</h2>");

  $id= $r->fetch_row();
  $id= $id[0];
}

$item= item_load($db, $id);

head("Item: " . ashtml($item['name']). " @ Scat");

include 'item-searchform.php';
?>
<form class="form-horizontal" role="form"
      data-bind="submit: saveItem">
  <div class="form-group">
    <label for="code" class="col-sm-2 control-label">
      <a class="text-left fa" id="active"
         data-bind="css: { 'fa-check-square-o' : item.active(), 'fa-square-o' : !item.active() }"></a>
      Code
    </label>
    <div class="col-sm-8">
      <p class="form-control-static"
         data-bind="jeditable: item.code"></p>
    </div>
  </div>
  <div class="form-group">
    <label for="name" class="col-sm-2 control-label">Name</label>
    <div class="col-sm-8">
      <p class="form-control-static"
         data-bind="jeditable: item.name"></p>
    </div>
  </div>
  <div class="form-group">
    <label for="brand" class="col-sm-2 control-label">Brand</label>
    <div class="col-sm-8">
      <select class="form-control" id="brand"
              data-bind="value: selectedBrand, foreach: brands">
        <option data-bind="text: name, value: id"></option>
      </select>
    </div>
  </div>
  <div class="form-group">
    <label for="retail_price" class="col-sm-2 control-label">List</label>
    <div class="col-sm-8">
      <p class="form-control-static"
         data-bind="jeditable: item.retail_price, jeditableOptions: { ondisplay: amount, data: item.retail_price() }"></p>
    </div>
  </div>
  <div class="form-group">
    <label for="sale_price" class="col-sm-2 control-label">Sale</label>
    <div class="col-sm-8">
      <p class="form-control-static"
         data-bind="text: amount(item.sale_price())"></p>
    </div>
  </div>
  <div class="form-group">
    <label for="discount" class="col-sm-2 control-label">Discount</label>
    <div class="col-sm-8">
      <p class="form-control-static"
         data-bind="jeditable: item.discount, jeditableOptions: { ondisplay: function() { return item.discount_label() ? item.discount_label() : amount(item.discount()) } , data : item.discount() }"></p>
    </div>
  </div>
  <div class="form-group">
    <label for="stock" class="col-sm-2 control-label">Stock</label>
    <div class="col-sm-8">
      <p class="form-control-static"
         data-bind="jeditable: item.stock"></p>
    </div>
  </div>
  <div class="form-group">
    <label for="minimum_quantity" class="col-sm-2 control-label">
      Minimum Quantity
    </label>
    <div class="col-sm-8">
      <p class="form-control-static"
         data-bind="jeditable: item.minimum_quantity"></p>
    </div>
  </div>

  <div class="form-group">
    <label for="barcodes" class="col-sm-2 control-label"><a id="print" class="fa fa-print"></a> Barcodes</label>
    <div class="col-sm-8">
      <table id="barcodes" class="table table-condensed">
        <tbody data-bind="foreach: item.barcode_list">
          <tr>
            <td><span data-bind="click: $parent.removeBarcode"><a class="fa fa-trash-o"></a></span></td>
            <td><span data-bind="text: $data.code"></span></td>
            <td><span data-bind="text: $data.quantity"></span></td>
          </tr>
        </tbody>
        <tfoot>
          <tr>
            <td id="new-barcode" colspan="3">
              <a class="fa fa-plus-square-o"></a>
            </td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>

  <div class="form-group" data-bind="visible: changed">
    <div class="col-sm-offset-2 col-sm-8">
      <button type="submit" class="btn btn-primary"
              data-loading-text="Processing...">
        Save
      </button>
    </div>
  </div>
</form>
<script>
$('#active').on('click', function(ev) {
  ev.preventDefault();
  var item= viewModel.item;

  $.getJSON("api/item-update.php?callback=?",
            { item: item.id(), active: item.active() ? 0 : 1 },
            function (data) {
              if (data.error) {
                alert(data.error);
                return;
              }
              loadItem(data.item);
            });
});
function editBarcodeQuantity(value, settings) {
  var item= viewModel.item;
  var row= $(this).closest('tr');
  var code= $('td:nth(0)', row).text();

  $.getJSON("api/item-barcode-update.php?callback=?",
            { item: item.id, code: code, quantity: value},
            function (data) {
              if (data.error) {
                alert(data.error);
                return;
              }
              loadItem(data.item);
            });
}
$('#new-barcode').editable(function(value, settings) {
  var item= viewModel.item;
  $.getJSON("api/item-barcode-update.php?callback=?",
            { item: item.id, code: value },
            function (data) {
              if (data.error) {
                alert(data.error);
                return;
              }
              loadItem(data.item);
            });
  return  $(this).data('original');
}, {
  event: 'click',
  style: 'display: inline',
  placeholder: '',
  data: function(value, settings) {
    $(this).data('original', value);
    return '';
  },
});
$('#print').on('click', function(ev) {
  ev.preventDefault();
  var item= viewModel.item.id();

  $.getJSON("print/labels-price.php?callback=?",
            { id: item },
            function (data) {
              if (data.error) {
                alert(data.error);
                return;
              }
            });
});
</script>
<?

$q= "SELECT company Company,
            retail_price List\$dollar,
            net_price Net\$dollar,
            promo_price Promo\$dollar
       FROM vendor_item
       JOIN person ON vendor_item.vendor = person.id
      WHERE item = $id";

echo '<h2 onclick="$(\'#vendors\').toggle()">Vendors</h2>';
echo '<div id="vendors" style="display: none">';
dump_table($db->query($q));
dump_query($q);
echo '</div>';

$r= $db->query("SET @count = 0");

$q= "SELECT DATE_FORMAT(created, '%a, %b %e %Y %H:%i') Date,
            CONCAT(txn, '|', txn.type, '|', txn.number) AS Transaction\$txn,
            CASE type
              WHEN 'customer' THEN IF(allocated <= 0, 'Sale', 'Return')
              WHEN 'vendor' THEN 'Stock'
              WHEN 'correction' THEN 'Correction'
              WHEN 'drawer' THEN 'Till Count'
              ELSE type
            END Type,
            IF(discount_type,
               CASE discount_type
                 WHEN 'percentage' THEN ROUND(retail_price * ((100 - discount) / 100), 2)
                 WHEN 'relative' THEN (retail_price - discount) 
                 WHEN 'fixed' THEN (discount)
               END,
               retail_price) AS Price\$dollar,
            allocated AS Quantity\$right,
            @count := @count + allocated AS Count\$right
       FROM txn_line
       JOIN txn ON (txn_line.txn = txn.id)
      WHERE item = $id
      GROUP BY txn
      ORDER BY created";

echo '<h2 onclick="$(\'#history\').toggle()">History</h2>';
echo '<div id="history" style="display: none">';
dump_table($db->query($q));
dump_query($q);
echo '</div>';

foot();
?>
<script>
var model= {
  search: '<?=ashtml($search);?>',
  all: <?=(int)$all?>,
  item: <?=json_encode($item);?>,
  brands: [],
};

var viewModel= ko.mapping.fromJS(model);

// ghetto change tracking
viewModel.saved= ko.observable(ko.toJSON(viewModel.item));
viewModel.changed= ko.computed(function() {
  return ko.toJSON(viewModel.item) != viewModel.saved();
});

$.getJSON('api/brand-list.php?verbose=1&callback=?')
  .done(function (data) {
    ko.mapping.fromJS({ brands: data }, viewModel);
    // make sure correct selection is made
    viewModel.item.brand_id.valueHasMutated();
  });

viewModel.selectedBrand= ko.computed({
  read: function () {
    return this.item.brand_id();
  },
  write: function (value) {
    if (typeof value != 'undefined' && value != '') {
      this.item.brand_id(value);
    }
  },
  owner: viewModel
}).extend({ notify: 'always' });

viewModel.removeBarcode= function(place) {
  $.getJSON("api/item-barcode-delete.php?callback=?",
            { item: viewModel.item.id, code: place.code },
            function (data) {
              if (data.error) {
                alert(data.error);
                return;
              }
              loadItem(data.item);
            });
}

ko.applyBindings(viewModel);

function loadItem(item) {
  ko.mapping.fromJS({ item: item }, viewModel);
  viewModel.saved(ko.toJSON(viewModel.item));
}

function saveItem(place) {
  $.getJSON("api/item-update.php?callback=?",
            ko.mapping.toJS(viewModel.item),
            function (data) {
              if (data.error) {
                alert(data.error);
                return;
              }
              loadItem(data.item);
            });
}

</script>
