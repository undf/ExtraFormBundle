uFormUtils.directive('uSubmit', ['$http', '$compile', function($http, $compile) {
    'use strict';
    return {
        restrict: 'A',
        transclude: false,
        controller: function($scope, $element, $attrs) {
            var formName = $attrs.name || $attrs.ngForm,
                    url = $attrs.uSubmit;

            function showSpinner() {
                $element.find('input[type=submit]').addClass('disabled');
            };

            function hideSpinner() {
                $element.find('input[type=submit]').removeClass('disabled');
            }

            $scope.showErrors = function() {
                $scope[formName].validated = true;
            };

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
                                //Value 'undefined' is serialized as a string, so get rid of them.
                                value = (value === undefined) ? null : value;
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
                    var newForm = angular.element(data);
                    $compile(newForm)($scope, function(clonedElement, scope) {
                        $element.replaceWith(clonedElement);
                        scope[formName].validated = false;
                    });
                    hideSpinner();
                }).error(function(data, status, headers, config) {
                    alert("failed!");
                    hideSpinner();
                });
            };
        },
        compile: function(tElement, tAttrs, transclude) {
            var submitter = tElement.find('input[type=submit]');

            if (submitter.length > 0)
                submitter.attr('data-ng-click', 'showErrors()');
            return {
                pre: function(scope, formElement, attr, controller) {
                    var name = attr.name || attr.ngForm,
                            submitter = tElement.find('input[type=submit]');


                    scope[name].validated = false;

                    formElement.attr('novalidate', '');

                    formElement.bind('submit', function(event) {
                        if (scope[name].$invalid) {
                            //Prevent the form to submit when pressing enter
                            event.preventDefault();
                            //Send ajax request
                            scope.save();

                        }
                        else if (!attr.action) {
                            //Prevent master request
                            event.preventDefault();
                            //Send ajax request
                            scope.save();
                        }


                    });
                }
            };
        }

    };

}]);