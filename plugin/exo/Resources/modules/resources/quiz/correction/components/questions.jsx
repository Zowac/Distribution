import React from 'react'
import {PropTypes as T} from 'prop-types'
import {connect} from 'react-redux'

import {trans} from '#/main/app/intl/translation'
import {TooltipOverlay} from '#/main/app/overlay/tooltip/components/overlay'

import {HtmlText} from '#/main/core/layout/components/html-text'

import {selectors as correctionSelectors} from '#/plugin/exo/resources/quiz/correction/store/selectors'

export const QuestionRow = props =>
  <tr>
    <td>
      <HtmlText>{props.question.title || props.question.content}</HtmlText>
    </td>
    <td>{props.answers.length}</td>
    <td className="actions-cell text-right">
      <TooltipOverlay
        id={props.question.id}
        tip={trans('correct', {}, 'actions')}
      >
        <a className="btn btn-link-default" href={`#correction/${props.question.id}`}>
          <span className="fa fa-fw fa-check-square-o" />
          <span className="sr-only">{trans('correct', {}, 'actions')}</span>
        </a>
      </TooltipOverlay>
    </td>
  </tr>

QuestionRow.propTypes = {
  question: T.shape({
    id: T.string.isRequired,
    title: T.string,
    content: T.string.isRequired,
    score: T.shape({
      type: T.string,
      max: T.number
    }).isRequired
  }).isRequired,
  answers: T.arrayOf(T.object)
}

const Questions = props =>
  props.questions.length > 0 ?
    <div className="questions-list">
      <table className="table table-striped table-hover">
        <thead>
          <tr>
            <th>{trans('question', {}, 'quiz')}</th>
            <th>{trans('number_of_papers_to_correct', {}, 'quiz')}</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          {props.questions.map((question, idx) =>
            <QuestionRow key={idx} {...question}/>
          )}
        </tbody>
      </table>
    </div> :
    <div className="questions-list">
      <div className="alert alert-warning">
        {trans('no_question_to_correct', {}, 'quiz')}
      </div>
    </div>

Questions.propTypes = {
  questions: T.arrayOf(T.object).isRequired
}

Questions.defaultProps = {
  questions: []
}

function mapStateToProps(state) {
  return {
    questions: correctionSelectors.questions(state)
  }
}

const ConnectedQuestions = connect(mapStateToProps)(Questions)

export {ConnectedQuestions as Questions}