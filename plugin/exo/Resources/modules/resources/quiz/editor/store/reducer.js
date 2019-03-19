import cloneDeep from 'lodash/cloneDeep'
import isEmpty from 'lodash/isEmpty'
import merge from 'lodash/merge'

import {makeReducer} from '#/main/app/store/reducer'
import {makeFormReducer} from '#/main/app/content/form/store/reducer'

import {makeId} from '#/main/core/scaffolding/id'
import {RESOURCE_LOAD} from '#/main/core/resource/store/actions'

import {Quiz, Step} from '#/plugin/exo/resources/quiz/prop-types'
import {
  QUIZ_ADD_STEP,
  QUIZ_COPY_STEP,
  QUIZ_MOVE_STEP,
  QUIZ_REMOVE_STEP
} from '#/plugin/exo/resources/quiz/editor/store/actions'

function setDefaults(quiz) {
  // adds default value to quiz data
  const formData = merge({}, Quiz.defaultProps, quiz)

  if (isEmpty(formData.steps)) {
    // adds an empty step
    formData.steps.push(createStep())
  }

  return formData
}

function createStep(stepData = {}) {
  return merge({id: makeId()}, Step.defaultProps, stepData)
}

function pushStep(step, steps, position) {
  const newSteps = cloneDeep(steps)

  switch (position.order) {
    case 'first':
      newSteps.unshift(step)
      break

    case 'before':
    case 'after':
      if ('before' === position.order) {
        newSteps.splice(steps.findIndex(step => step.id === position.step), 0, step)
      } else {
        newSteps.splice(steps.findIndex(step => step.id === position.step) + 1, 0, step)
      }
      break

    case 'last':
      newSteps.push(step)
      break
  }

  return newSteps
}

export const reducer = makeFormReducer('resource.editor', {}, {
  pendingChanges: makeReducer(false, {
    [QUIZ_ADD_STEP]: () => true,
    [QUIZ_COPY_STEP]: () => true,
    [QUIZ_MOVE_STEP]: () => true,
    [QUIZ_REMOVE_STEP]: () => true
  }),
  originalData: makeReducer({}, {
    [RESOURCE_LOAD]: (state, action) => setDefaults(action.resourceData.quiz) || state
  }),
  data: makeReducer({}, {
    /**
     * Fills form when the quiz data are loaded.
     *
     * @param {object} state - the quiz object @see Quiz.propTypes
     */
    [RESOURCE_LOAD]: (state, action) => setDefaults(action.resourceData.quiz) || state,

    /**
     * Adds a new step to the quiz.
     *
     * @param {object} state - the quiz object @see Quiz.propTypes
     */
    [QUIZ_ADD_STEP]: (state, action) => {
      const newState = cloneDeep(state)
      const newStep = createStep(action.step)

      newState.steps.push(newStep)

      return newState
    },

    /**
     * Creates a copy af a copy and push it at the requested position.
     *
     * @param {object} state - the quiz object @see Quiz.propTypes
     */
    [QUIZ_COPY_STEP]: (state, action) => {
      const newState = cloneDeep(state)

      const original = newState.steps.find(step => step.id === action.id)
      if (original) {
        // create a copy of the step
        const copy = cloneDeep(original)
        copy.id = makeId()

        // TODO : replace items ids

        // push created step in the list
        newState.steps = pushStep(copy, newState.steps, action.position)
      }

      return newState
    },

    /**
     * Moves a step to another position.
     *
     * @param {object} state - the quiz object @see Quiz.propTypes
     */
    [QUIZ_MOVE_STEP]: (state, action) => {
      const newState = cloneDeep(state)

      const currentPos = newState.steps.findIndex(step => step.id === action.id)
      if (-1 !== currentPos) {
        const currentStep = newState.steps.splice(currentPos, 1)

        newState.steps = pushStep(currentStep[0], newState.steps, action.position)
      }

      return newState
    },

    /**
     * Removes a step from the quiz.
     *
     * @param {object} state - the quiz object @see Quiz.propTypes
     */
    [QUIZ_REMOVE_STEP]: (state, action) => {
      const newState = cloneDeep(state)

      const stepPosition = newState.steps.findIndex(step => step.id === action.id)
      if (-1 !== stepPosition) {
        newState.steps.splice(stepPosition, 1)
      }

      return newState
    }
  })
})