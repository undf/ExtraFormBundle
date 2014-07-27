uFormUtils
    .controller('CollectionContainerController', ['$scope', '$element', '$attrs', '$compile', function CollectionContainerCtrl($scope, $element, $attrs, $compile) {
        var prototype_name = '__name__',
            prototype_label_name = '__item_count__',
            replace_pattern = new RegExp(prototype_name, 'g'),
            replace_label_pattern = new RegExp(prototype_label_name, 'g');

        var ctrl = this;
        ctrl.setLabels = function() {
            var prototypeLabel = $attrs.prototypeLabel,
                labels = $element.find('.collection-item .control-label');

            angular.forEach(labels, function(label, key) {
                angular.element(label).text(prototypeLabel.replace(replace_label_pattern, key + 1));
            })
        };

        ctrl.add = function() {
            var index, rowContent;

            index = $element.find('.collection-items').find('.collection-item').length;

            rowContent = $attrs.prototype.replace(replace_pattern, index);

            $compile(rowContent)($scope, function(clonedElement) {
                clonedElement.find('.control-label').text($attrs.prototypeLabel.replace(replace_label_pattern, index + 1));
                $element.find('.collection-items').append(clonedElement);
            })
        };

        ctrl.remove = function(elemClass) {
            var inputForms = [],
                formName = $element.closest('form').attr('name'),
                form = $scope[formName];

            angular.forEach(angular.element(elemClass + ' input'), function(input) {
                this.push(form[angular.element(input).attr('name')]);
            }, inputForms);

            angular.element(elemClass).remove();

            angular.forEach(inputForms, function(inputForm) {
                form.$removeControl(inputForm);
            });

            ctrl.setLabels();
        };
    }])

    .directive('collectionContainer', ['$http', '$compile', function($http, $compile) {
        'use strict';
        return {
            restrict: 'A',
            transclude: false,
            controller: 'CollectionContainerController',
            compile: function() {
                return {
                    pre: function(scope, formElement, attr, controller) {
                        controller.setLabels();
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
            require: "^collectionContainer",
            scope: {},
            controller: function($scope, $element, $attrs) {
            },
            compile: function() {
                return {
                    pre: function(scope, formElement, attr, collectionContainerCtrl) {
                        formElement.bind('click', function(event) {
                            collectionContainerCtrl.add();
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
            require: "^collectionContainer",
            scope: {},
            compile: function() {
                return {
                    pre: function(scope, formElement, attr, collectionContainerCtrl) {
                        formElement.bind('click', function(event) {
                            collectionContainerCtrl.remove(attr.collectionRemoveBtn);
                        });
                    }
                };
            }
        };
    }]);
