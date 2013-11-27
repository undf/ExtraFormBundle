uFormUtils.directive('uSubmit', ['$http', '$compile', function($http, $compile) {
    'use strict';
    return {
        restrict: 'A',
        transclude: false,
        controller: function($scope, $element, $attrs) {
            var formName = $attrs.name || $attrs.ngForm,
                url = $attrs.uSubmit;

            function getSubmitter() {
                var submitter = $element.find('input[type=submit]');
                if (submitter.length === 0) {
                    var formId = $element.attr('id');
                    submitter = angular.element('input[type="submit"][form="' + formId + '"]');
                }
                return submitter;
            }

            function showSpinner() {
                $scope.$emit('spinner.show');
                getSubmitter().addClass('disabled');
            };

            function hideSpinner() {
                $scope.$emit('spinner.hide')
                getSubmitter().removeClass('disabled');
            }

            $scope.save = function() {
                showSpinner();

                //Parse field name in order to get all parent names from the form tree
                //For instance, for fieldName="myform[child1][child2][child3]" it will
                //return ['child1', 'child2, 'child3]
                var parseFields = function(fieldName) {
                    var regex = /(\w+)+/gi;
                    var matches = fieldName.match(regex);
                    return matches.slice(1);
                };
                $http({
                    method: 'POST',
                    url: url,
                    //IMPORTANT!!! You might think this should be set to 'multipart/form-data'
                    // but this is not true because when we are sending up files the request
                    // needs to include a 'boundary' parameter which identifies the boundary
                    // name between parts in this multi-part request and setting the Content-type
                    // manually will not set this boundary parameter. For whatever reason,
                    // setting the Content-type to 'false' will force the request to automatically
                    // populate the headers properly including the boundary parameter.
                    headers: {'Content-Type': false},
                    //This method will allow us to change how the data is sent up to the server
                    // for which we'll need to encapsulate the model data in 'FormData'
                    transformRequest: function(data) {
                        var formData = new FormData();
                        angular.forEach(data.model, function(fieldValue, fieldName) {
                            //Get only those properties whose name corresponds with the name of form field.
                            if (fieldName.indexOf(formName) === 0) {
                                //Parse field name to get all field names along the form tree
                                var fields = parseFields(fieldName),
                                    value;

                                //Find the ngModel property which holds the value which
                                //corresponds to this fieldName
                                value = this[fields[0]];
                                for (var i = 1; i < fields.length; i++) {
                                    value = value ? value[fields[i]] : undefined;
                                }
                                //Values 'undefined' and 'null' is serialized as a string, so get rid of them.
                                value = (value === undefined || value === null) ? '' : value;
                                //Need to convert our json object to a string version of json otherwise
                                // the browser will do a 'toString()' on the object which will result
                                // in the value '[Object object]' on the server.
                                value = angular.isObject(value) ? angular.toJson(value) : value;
                                formData.append(fieldName, value);
                            }
                        }, data.model);
                        //now add all of the assigned files
                        for (var i = 0; i < data.files.length; i++) {
                            //add each file to the form data and iteratively name them
                            //in case there are more than one
                            formData.append(data.files[i].name + (data.files.length > 1 ? i : ''), data.files[i].file);
                        }
                        return formData;
                    },
                    //Create an object that contains the model and files which will be transformed
                    // in the above transformRequest method
                    data: {model: $scope[formName], files: $scope[formName].formFiles || []}
                }).success(function(data, status, headers, config) {

                        hideSpinner();
                        if (angular.isString(data)) {
                            var newForm = angular.element(data).find('form');
                            if (angular.isUndefined(newForm)) {
                                newForm = angular.element(data);
                            }
                            $compile(newForm)($scope, function(clonedElement, scope) {
                                $element.replaceWith(clonedElement);
                                scope[formName].validated = false;
                            });
                        }
                        if ($scope[formName].hasErrors) {
                            $scope.$emit('submit.error', $scope[formName], data, status, headers, config);
                        } else {
                            $scope.$emit('submit.success', $scope[formName], data, status, headers, config);
                        }

                    }).error(function(data, status, headers, config) {
                        hideSpinner();
                        if (angular.isString(data)) {
                            var newForm = angular.element(data).find('form');
                            if (newForm.length == 0) {
                                newForm = angular.element(data);
                            }
                            $compile(newForm)($scope, function(clonedElement, scope) {
                                $element.replaceWith(clonedElement);
                                scope[formName].validated = false;
                            });
                        }
                        $scope.$emit('submit.error', $scope[formName], data, status, headers, config);
                    });
                $scope.$apply();
            };
        },
        compile: function(tElement, tAttrs, transclude) {

            return {
                pre: function(scope, formElement, attr, controller) {

                    var name = attr.name || attr.ngForm;

                    scope[name].validated = false;

                    formElement.attr('novalidate', '');

                    formElement.bind('submit', function(event) {
                        scope[name].validated = true;
                        if (scope[name].$invalid) {
                            scope.$emit('submit.error', scope[name]);
                            //Prevent the form to submit when pressing enter
                            event.preventDefault();

                        }
                        else if (!attr.action) {
                            // The error did not have errors:
                            scope[name].hasErrors = false;

                            //Prevent master request
                            event.preventDefault();
                            //Send ajax request
                            scope.save();
                        }
                        scope.$apply();


                    });
                }
            };
        }

    };

}]);
