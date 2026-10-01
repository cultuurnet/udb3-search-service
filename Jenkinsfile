pipeline {
    options {
        disableRestartFromStage()
    }

    agent none

    environment {
        PIPELINE_VERSION = util.pipelineVersion()
        SOURCE_URL       = 'https://github.com/cultuurnet/udb3-search-service'
        APT_REPOSITORY   = 'uitdatabank-search-api'
        ECR_REGISTRY     = '757200591793.dkr.ecr.eu-west-1.amazonaws.com'
        ECR_REPOSITORY   = 'uitdatabank/search-api'
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
                    agent { label 'docker_build' }
                    environment {
                        GIT_SHORT_COMMIT = util.shortCommitRef()
                        IMAGE_URI        = "${env.ECR_REGISTRY}/${env.ECR_REPOSITORY}:${env.PIPELINE_VERSION}"
                    }
                    steps {
                        sh label: 'Build image', script: """
                            docker build \\
                                -f docker/Dockerfile \\
                                --tag ${env.IMAGE_URI} \\
                                --tag ${env.ECR_REGISTRY}/${env.ECR_REPOSITORY}:latest \\
                                --label org.opencontainers.image.revision=${env.GIT_SHORT_COMMIT} \\
                                --label org.opencontainers.image.version=${env.PIPELINE_VERSION} \\
                                --label org.opencontainers.image.source=${env.SOURCE_URL} \\
                                .
                        """

                        sh label: 'Push image', script: "docker push ${env.IMAGE_URI}"
                        sh label: 'Push image', script: "docker push ${env.ECR_REGISTRY}/${env.ECR_REPOSITORY}:latest"

                        echo "Pushed: ${env.IMAGE_URI}"
                    }
                    post {
                        cleanup {
                            catchError(
                                buildResult: 'SUCCESS',
                                stageResult: 'UNSTABLE',
                                message: 'Cleanup failed'
                            ) {
                                sh "docker rmi ${env.IMAGE_URI}"
                                sh "docker rmi ${env.ECR_REGISTRY}/${env.ECR_REPOSITORY}:latest"
                                cleanWs()
                            }
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
                uploadAptlyArtifacts artifacts: '*.deb', repository: env.APT_REPOSITORY
                createAptlySnapshot name: "${env.APT_REPOSITORY}-${env.PIPELINE_VERSION}", repository: env.APT_REPOSITORY
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
                publishAptlySnapshot snapshotName: "${env.APT_REPOSITORY}-${env.PIPELINE_VERSION}", publishTarget: "${env.APT_REPOSITORY}-${env.APPLICATION_ENVIRONMENT}", distributions: ['focal', 'noble']
            }
        }

        stage('Deploy to acceptance') {
            agent any
            options { skipDefaultCheckout() }
            environment {
                APPLICATION_ENVIRONMENT = 'acceptance'
            }
            stages {
                stage('Publish snapshot / promote docker image'){
                    parallel {
                        stage('Publish snapshot') {
                            steps {
                                publishAptlySnapshot snapshotName: "${env.APT_REPOSITORY}-${env.PIPELINE_VERSION}", publishTarget: "${env.APT_REPOSITORY}-${env.APPLICATION_ENVIRONMENT}", distributions: ['focal', 'noble']
                            }
                        }
                        stage('Promote docker image') {
                            steps {
                                promoteDockerImage repository: env.ECR_REPOSITORY, sourceTag: env.PIPELINE_VERSION, targetTag: env.APPLICATION_ENVIRONMENT
                            }
                        }
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

        stage('Run acceptance tests') {
            when {
                expression { params.RUN_ACCEPTANCE_TESTS }
            }
            agent { label 'ubuntu && 20.04 && docker' }
            steps {
                build job: 'uitdatabank-acceptance-tests', wait: true
            }
        }

        stage('Deploy to testing') {
            agent { label 'ubuntu && 20.04' }
            options { skipDefaultCheckout() }
            environment {
                APPLICATION_ENVIRONMENT = 'testing'
            }

            stages {
                stage('Publish snapshot / promote docker image'){
                    parallel {
                        stage('Publish snapshot') {
                            steps {
                                publishAptlySnapshot snapshotName: "${env.APT_REPOSITORY}-${env.PIPELINE_VERSION}", publishTarget: "${env.APT_REPOSITORY}-${env.APPLICATION_ENVIRONMENT}", distributions: ['focal', 'noble']
                            }
                        }
                        stage('Promote docker image') {
                            steps {
                                promoteDockerImage repository: env.ECR_REPOSITORY, sourceTag: env.PIPELINE_VERSION, targetTag: env.APPLICATION_ENVIRONMENT
                            }
                        }
                    }
                }
                stage('Deploy') {
                    parallel {
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
            agent { label 'ubuntu && 20.04' }
            options { skipDefaultCheckout() }
            environment {
                APPLICATION_ENVIRONMENT = 'production'
            }

            stages {
                stage('Publish snapshot') {
                    steps {
                        publishAptlySnapshot snapshotName: "${env.APT_REPOSITORY}-${env.PIPELINE_VERSION}", publishTarget: "${env.APT_REPOSITORY}-${env.APPLICATION_ENVIRONMENT}", distributions: ['focal', 'noble']
                    }
                }
                stage('Deploy') {
                    parallel {
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
                    cleanupAptlySnapshots repository: env.APT_REPOSITORY
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
