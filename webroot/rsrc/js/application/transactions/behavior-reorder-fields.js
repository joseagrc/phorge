/**
 * @provides javelin-behavior-editengine-reorder-fields
 * @requires javelin-behavior
 *           javelin-stratcom
 *           javelin-workflow
 *           javelin-dom
 *           phorge-draggable-list
 */

JX.behavior('editengine-reorder-fields', function(config) {

  var root = JX.$(config.listID);

  var list = new JX.DraggableList('editengine-form-field', root)
    .setFindItemsHandler(function() {
      return JX.DOM.scry(root, 'li', 'editengine-form-field');
    });

  list.listen('didDrop', function() {
    var nodes = list.findItems();

    var data;
    var keys = [];
    for (var ii = 0; ii < nodes.length; ii++) {
      data = JX.Stratcom.getData(nodes[ii]);
      keys.push(data.fieldKey);
    }

    JX.$(config.inputID).value = keys.join(',');
  });

});
