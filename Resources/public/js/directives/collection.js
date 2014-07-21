uFormUtils
    .directive('collectionContainer', ['$http', '$compile', function($http, $compile) {
        'use strict';
        var prototype_name = '__name__',
            prototype_label_name = '__item_count__',
            replace_pattern = new RegExp(prototype_name, 'g'),
            replace_label_pattern = new RegExp(prototype_label_name, 'g');

        return {
            restrict: 'A',
            transclude: false,
            controller: function($scope, $element, $attrs) {
                $scope.add = function() {
                    var index, rowContent;

                    index = $element.find('.collection-items').find('.collection-item').length;

                    rowContent = $attrs.prototype.replace(replace_label_pattern, index + 1);
                    rowContent = rowContent.replace(replace_pattern, index);

                    $compile(rowContent)($scope, function(clonedElement) {
                        $element.find('.collection-items').append(clonedElement);
                    })
                }

                $scope.remove = function(elemClass) {
                    $element.find(elemClass).remove();
                }
            },
            compile: function() {
                return {
                    pre: function(scope, formElement, attr) {
                        var labels = formElement.find('.collection-item .control-label');
                        angular.forEach(labels, function(label, key) {
                            label = angular.element(label)

                            var html = label.html().replace(replace_label_pattern, key + 1);
                            label.html(html);
                        })
                    }
                };
            }

        };

    }])
.directive('collectionAddBtn', ['$http', '$compile', function($http, $compile) {
    'use strict';
    return {
        restrict: 'A',
        transclude: false,
        require: "?collectionContainer",
        compile: function() {
            return {
                pre: function(scope, formElement, attr) {
                    formElement.bind('click', function(event) {
                        scope.add();
                    });
                }
            };
        }

    };

}])
    .directive('collectionRemoveBtn', ['$http', '$compile', function($http, $compile) {
        'use strict';
        return {
            restrict: 'A',
            transclude: false,
            require: "?collectionContainer",
            compile: function() {
                return {
                    pre: function(scope, formElement, attr) {
                        formElement.bind('click', function(event) {
                            scope.remove(attr.collectionRemoveBtn);

                        });
                    }
                };
            }
        };
    }]);
