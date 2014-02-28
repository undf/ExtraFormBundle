uFormUtils.directive('uImageUpload', function() {
    'use strict';
    return {
        restrict: 'EA',
        transclude: false,
        controller: function($scope, $element, $attrs) {
            var formName = $element.closest('form').attr('name');
            $scope[formName].entity = {};
            $scope[formName].formFiles = [];

            $scope.showImages = function(input, files) {
                //iterate files since 'multiple' may be specified on the element
                angular.forEach(files, function(file) {
                    var reader = new FileReader(),
                        formFile = {
                            name: angular.element(input).attr('name'),
                            file: file
                        };

                    reader.onload = function(e) {
                        $scope.$apply(function() {
                            $scope[formName].entity[$attrs.uFileName] = e.timeStamp;
                            $scope[formName].formFiles.push(formFile);
                            $scope[$attrs.uFileSrc] = e.target.result;
                        });
                    };

                    reader.readAsDataURL(file);

                });
            };
            $scope.uploadFile = function() {
                $element.find('[type=file]').click();
            };
            $scope.removePic = function() {
                //Clear the file input so the onchange event is still
                //triggered when uploading same image twice in a row.
//                var clearInput = function (source) {
//                    var $form = angular.element('<form>'),
//                        $targ = source.clone().appendTo($form);
//                    $form[0].reset();
//                    source.replaceWith($targ);
//                };
//                clearInput($element.find('[type=file]'));
                $scope[$attrs.uFileSrc] = $scope[$attrs.uFileDefaultSrc];
                $scope[formName].entity[$attrs.uFileName] = '';
            };

        },
        compile: function() {
            return {
                pre: function(scope, formElement, attr, controller) {
                    formElement.find('[type=file]').bind('change', function(event) {
                        scope.showImages(this, event.target.files);
                    });
                }
            };
        }

    };

});


