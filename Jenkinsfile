pipeline {
    options {
        disableRestartFromStage()
    }

    agent none

    environment {
        PIPELINE_VERSION = util.pipelineVersion()
        REPOSITORY_NAME  = 'uitdatabank-search-api'
        ECR_REGISTRY     = '757200591793.dkr.ecr.eu-west-1.amazonaws.com'
        ECR_REPOSITORY   = 'uitdatabank/search-api'
        AWS_REGION       = 'eu-west-1'
    }

    stages {
        stage('Pre build') {
            steps {
                setBuildDisplayName to: env.PIPELINE_VERSION
                sendBuildNotification()
            }
        }

        stage('Build') {
            parallel {
                stage('Build deb package') {
                    agent { label 'ubuntu && 20.04 && php8.1' }
                    environment {
                        GIT_SHORT_COMMIT = util.shortCommitRef()
                        ARTIFACT_VERSION = "${env.PIPELINE_VERSION}" + '+sha.' + "${env.GIT_SHORT_COMMIT}"
                    }
                    steps {
                        sh label: 'Install rubygems', script: 'bundle install --deployment'
                        sh label: 'Build binaries', script: 'bundle exec rake build'
                        sh label: 'Build artifact', script: "bundle exec rake build_artifact ARTIFACT_VERSION=${env.ARTIFACT_VERSION}"
                        archiveArtifacts artifacts: "pkg/*${env.ARTIFACT_VERSION}*.deb", onlyIfSuccessful: true
                    }
                    post {
                        cleanup {
                            cleanWs()
                        }
                    }
                }

                stage('Build & push docker image') {
                    agent { label 'docker && nodejs22 && php8.1' } // node & php version specified to ensure run in agent with increased volume size for docker build
                    environment {
                        GIT_SHORT_COMMIT = util.shortCommitRef()
                        IMAGE_TAG        = "${env.PIPELINE_VERSION}"
                        IMAGE_URI        = "${env.ECR_REGISTRY}/${env.ECR_REPOSITORY}:${env.IMAGE_TAG}"
                    }
                    steps {
                        sh label: 'Build image', script: """
                            docker build \\
                                --tag ${env.IMAGE_URI} \\
                                --tag ${env.ECR_REGISTRY}/${env.ECR_REPOSITORY}:latest \\
                                --label org.opencontainers.image.revision=${env.GIT_SHORT_COMMIT} \\
                                --label org.opencontainers.image.version=${env.PIPELINE_VERSION} \\
                                --label org.opencontainers.image.source=https://github.com/cultuurnet/udb3-search-service \\
                                .
                        """

                        sh label: 'Push image', script: """
                            docker push ${env.IMAGE_URI}
                            docker push ${env.ECR_REGISTRY}/${env.ECR_REPOSITORY}:latest
                        """

                        echo "Pushed: ${env.IMAGE_URI}"
                    }
                    post {
                        cleanup {
                            sh "docker rmi ${env.IMAGE_URI} || true"
                            sh "docker rmi ${env.ECR_REGISTRY}/${env.ECR_REPOSITORY}:latest || true"
                            cleanWs()
                        }
                    }
                }
            }
        }

        stage('Upload artifact') {
            agent any
            options { skipDefaultCheckout() }
            steps {
                copyArtifacts filter: 'pkg/*.deb', projectName: env.JOB_NAME, flatten: true, selector: specific(env.BUILD_NUMBER)
                uploadAptlyArtifacts artifacts: '*.deb', repository: env.REPOSITORY_NAME
                createAptlySnapshot name: "${env.REPOSITORY_NAME}-${env.PIPELINE_VERSION}", repository: env.REPOSITORY_NAME
            }
            post {
                cleanup {
                    cleanWs()
                }
            }
        }

        stage('Deploy to development') {
            agent any
            options { skipDefaultCheckout() }
            environment {
                APPLICATION_ENVIRONMENT = 'development'
            }
            steps {
                publishAptlySnapshot snapshotName: "${env.REPOSITORY_NAME}-${env.PIPELINE_VERSION}", publishTarget: "${env.REPOSITORY_NAME}-${env.APPLICATION_ENVIRONMENT}", distributions: ['focal', 'noble']
            }
        }

        stage('Deploy to acceptance') {
            agent any
            options { skipDefaultCheckout() }
            environment {
                APPLICATION_ENVIRONMENT = 'acceptance'
            }
            stages {
                stage('Publish snapshot') {
                    steps {
                        publishAptlySnapshot snapshotName: "${env.REPOSITORY_NAME}-${env.PIPELINE_VERSION}", publishTarget: "${env.REPOSITORY_NAME}-${env.APPLICATION_ENVIRONMENT}", distributions: ['focal', 'noble']
                    }
                }
                stage('Promote docker image') {
                    steps {
                        promoteDockerImage repository: env.ECR_REPOSITORY, sourceTag: env.PIPELINE_VERSION, targetTag: 'acceptance', region: env.AWS_REGION
                    }
                }
                stage('Deploy') {
                    parallel {
                        stage('Deploy to ElasticSearch 8 node (instance)') {
                            steps {
                                triggerDeployment nodeName: 'uitdatabank-search-acc02'
                            }
                        }
                        stage('Deploy to ElasticSearch 8 node (docker)') {
                            steps {
                                triggerDeployment nodeName: 'uitdatabank-search-docker-acc01', timeout: 600
                            }
                        }
                    }
                }
            }
            post {
                always {
                    sendBuildNotification to: '#upw-ops', message: "Pipeline <${env.RUN_DISPLAY_URL}|${util.getJobDisplayName()} [${currentBuild.displayName}]>: deployed to *${env.APPLICATION_ENVIRONMENT}*"
                }
            }
        }

        stage('Deploy to testing') {
            input { message "Deploy to Testing?" }
            agent { label 'ubuntu && 20.04' }
            options { skipDefaultCheckout() }
            environment {
                APPLICATION_ENVIRONMENT = 'testing'
            }

            stages {
                stage('Publish snapshot') {
                    steps {
                        publishAptlySnapshot snapshotName: "${env.REPOSITORY_NAME}-${env.PIPELINE_VERSION}", publishTarget: "${env.REPOSITORY_NAME}-${env.APPLICATION_ENVIRONMENT}", distributions: ['focal', 'noble']
                    }
                }
                stage('Promote docker image') {
                    steps {
                        promoteDockerImage repository: env.ECR_REPOSITORY, sourceTag: env.PIPELINE_VERSION, targetTag: 'testing', region: env.AWS_REGION
                    }
                }
                stage('Deploy') {
                    parallel {
                        stage('Deploy to first ElasticSearch 5 node') {
                            steps {
                                triggerDeployment nodeName: 'uitdatabank-search-test01'
                            }
                        }
                        stage('Deploy to second ElasticSearch 5 node') {
                            steps {
                                triggerDeployment nodeName: 'uitdatabank-search-test02'
                            }
                        }
                        stage('Deploy to ElasticSearch 8 node') {
                            steps {
                                triggerDeployment nodeName: 'uitdatabank-search-test03'
                            }
                        }
                        stage('Deploy to ElasticSearch 8 docker node') {
                            steps {
                                triggerDeployment nodeName: 'uitdatabank-search-docker-test01'
                            }
                        }
                    }
                }
            }
            post {
                always {
                    sendBuildNotification to: '#upw-ops', message: "Pipeline <${env.RUN_DISPLAY_URL}|${util.getJobDisplayName()} [${currentBuild.displayName}]>: deployed to *${env.APPLICATION_ENVIRONMENT}*"
                }
            }
        }

        stage('Deploy to production') {
            input { message "Deploy to Production?" }
            agent { label 'ubuntu && 20.04' }
            options { skipDefaultCheckout() }
            environment {
                APPLICATION_ENVIRONMENT = 'production'
            }

            stages {
                stage('Publish snapshot') {
                    steps {
                        publishAptlySnapshot snapshotName: "${env.REPOSITORY_NAME}-${env.PIPELINE_VERSION}", publishTarget: "${env.REPOSITORY_NAME}-${env.APPLICATION_ENVIRONMENT}", distributions: ['focal', 'noble']
                    }
                }
                stage('Deploy') {
                    parallel {
                        stage('Deploy to first ElasticSearch 5 node') {
                            steps {
                                triggerDeployment nodeName: 'uitdatabank-search-prod01'
                            }
                        }
                        stage('Deploy to second ElasticSearch 5 node') {
                            steps {
                                triggerDeployment nodeName: 'uitdatabank-search-prod02'
                            }
                        }
                        stage('Deploy to first ElasticSearch 8 node') {
                            steps {
                                triggerDeployment nodeName: 'uitdatabank-search-prod03'
                            }
                        }
                        stage('Deploy to second ElasticSearch 8 node') {
                            steps {
                                triggerDeployment nodeName: 'uitdatabank-search-prod04'
                            }
                        }
                    }
                }
            }
            post {
                always {
                    sendBuildNotification to: '#upw-ops', message: "Pipeline <${env.RUN_DISPLAY_URL}|${util.getJobDisplayName()} [${currentBuild.displayName}]>: deployed to *${env.APPLICATION_ENVIRONMENT}*"
                }
                cleanup {
                    cleanupAptlySnapshots repository: env.REPOSITORY_NAME
                }
            }
        }

        stage('Tag release') {
            options { skipDefaultCheckout() }

            agent any
            steps {
                copyArtifacts filter: 'pkg/*.deb', projectName: env.JOB_NAME, flatten: true, selector: specific(env.BUILD_NUMBER)
                tagRelease commitHash: artifact.metadata(artifactFilter: '*.deb', field: 'git-ref')
            }
            post {
                cleanup {
                    cleanWs()
                }
            }
        }
    }

    post {
        always {
            sendBuildNotification()
        }
    }
}
